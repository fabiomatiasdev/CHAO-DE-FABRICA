<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class DashboardController extends Controller
{
    /**
     * Rota raiz redireciona conforme a sessão.
     */
    public function root(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] === true && !isset($_SESSION['impersonate'])) {
            $this->redirect('/superadmin');
        } elseif (isset($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
        } else {
            $this->redirect('/login');
        }
    }

    /**
     * Dashboard Geral do Tenant.
     */
    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'] ?? null;
        if (!$tenantId) {
            $this->redirect('/login');
        }

        // --- LINHA 1 DE CARDS ---
        // 1. Modelos Ativos
        $modelosAtivos = Database::fetch(
            "SELECT COUNT(*) as total FROM produtos_modelos WHERE tenant_id = :tenant_id AND status = 'ativo'",
            ['tenant_id' => $tenantId]
        )['total'] ?? 0;

        // 2. Itens em Estoque (Matérias-Primas)
        $itensEstoque = Database::fetch(
            "SELECT COUNT(*) as total FROM materias_primas WHERE tenant_id = :tenant_id",
            ['tenant_id' => $tenantId]
        )['total'] ?? 0;

        // 3. OPs em Andamento e Carga de Fábrica
        $opsAndamento = Database::fetch(
            "SELECT COUNT(*) as total FROM ordens_producao WHERE tenant_id = :tenant_id AND status = 'em andamento'",
            ['tenant_id' => $tenantId]
        )['total'] ?? 0;

        // Carga de fábrica = (Qtd de peças em produção nas OPs em andamento / capacidade total das oficinas ativas) * 100
        $capacidadeTotal = Database::fetch(
            "SELECT SUM(capacidade_produtiva) as total FROM oficinas_faccoes WHERE tenant_id = :tenant_id AND status = 'ativo'",
            ['tenant_id' => $tenantId]
        )['total'] ?? 0;

        $pecasEmProducao = Database::fetch(
            "SELECT SUM(quantidade) as total FROM ordens_producao WHERE tenant_id = :tenant_id AND status = 'em andamento'",
            ['tenant_id' => $tenantId]
        )['total'] ?? 0;

        $cargaFabrica = 0;
        if ($capacidadeTotal > 0) {
            $cargaFabrica = round(($pecasEmProducao / $capacidadeTotal) * 100, 1);
        }

        // 4. Valor em Estoque (Matéria-Prima + Produtos Acabados)
        // a) Valor total em estoque de matérias-primas
        $valorMaterias = Database::fetch(
            "SELECT SUM(estoque_atual * custo_unitario) as total FROM materias_primas WHERE tenant_id = :tenant_id",
            ['tenant_id' => $tenantId]
        )['total'] ?? 0;

        // b) Valor total em estoque de produtos acabados
        // Precisamos calcular o custo de cada produto a partir de sua ficha técnica (MP + Mão de Obra)
        // e multiplicar pela quantidade atual em estoque (calculada pelas movimentações de entrada/saída).
        $modelos = Database::fetchAll(
            "SELECT id FROM produtos_modelos WHERE tenant_id = :tenant_id",
            ['tenant_id' => $tenantId]
        );

        $valorProdutosAcabados = 0;
        foreach ($modelos as $m) {
            $modeloId = $m['id'];
            
            // Custo da matéria-prima da ficha técnica
            $custoMP = Database::fetch(
                "SELECT SUM(fti.quantidade * mp.custo_unitario) as total 
                 FROM fichas_tecnicas_itens fti 
                 JOIN materias_primas mp ON fti.materia_prima_id = mp.id 
                 JOIN fichas_tecnicas ft ON fti.ficha_tecnica_id = ft.id
                 WHERE ft.produto_modelo_id = :modelo_id",
                ['modelo_id' => $modeloId]
            )['total'] ?? 0;

            // Custo de mão de obra da ficha técnica
            $custoMO = Database::fetch(
                "SELECT custo_mao_obra FROM fichas_tecnicas WHERE produto_modelo_id = :modelo_id",
                ['modelo_id' => $modeloId]
            )['custo_mao_obra'] ?? 0;

            $custoTotalPeca = $custoMP + $custoMO;

            // Quantidade em estoque calculada a partir de movimentações
            $movimentacoes = Database::fetch(
                "SELECT SUM(CASE WHEN tipo_movimentacao = 'entrada' THEN quantidade ELSE -quantidade END) as total 
                 FROM estoque_movimentacoes 
                 WHERE tenant_id = :tenant_id AND tipo_item = 'produto_acabado' AND item_id = :modelo_id",
                ['tenant_id' => $tenantId, 'modelo_id' => $modeloId]
            )['total'] ?? 0;

            if ($movimentacoes > 0) {
                $valorProdutosAcabados += ($movimentacoes * $custoTotalPeca);
            }
        }

        $valorEstoqueTotal = $valorMaterias + $valorProdutosAcabados;


        // --- LINHA 2 DE CARDS ---
        // 5. Total Produzido
        $totalProduzido = Database::fetch(
            "SELECT SUM(quantidade_retornada_boa) as total FROM retornos_faccao WHERE tenant_id = :tenant_id",
            ['tenant_id' => $tenantId]
        )['total'] ?? 0;

        // 6. Total Perdido
        $totalPerdido = Database::fetch(
            "SELECT SUM(quantidade_defeito_perda) as total FROM retornos_faccao WHERE tenant_id = :tenant_id",
            ['tenant_id' => $tenantId]
        )['total'] ?? 0;

        // 7. Valor das Perdas (Financeiro)
        // Valor das perdas = quantidade_defeito_perda * custo_modelo da OP correspondente
        $retornos = Database::fetchAll(
            "SELECT rf.quantidade_defeito_perda, op.produto_modelo_id 
             FROM retornos_faccao rf
             JOIN ordens_producao op ON rf.ordem_producao_id = op.id
             WHERE rf.tenant_id = :tenant_id",
            ['tenant_id' => $tenantId]
        );

        $valorPerdas = 0;
        foreach ($retornos as $r) {
            $modeloId = $r['produto_modelo_id'];
            $qtdPerda = $r['quantidade_defeito_perda'];

            if ($qtdPerda > 0) {
                // Obter custo do modelo
                $custoMP = Database::fetch(
                    "SELECT SUM(fti.quantidade * mp.custo_unitario) as total 
                     FROM fichas_tecnicas_itens fti 
                     JOIN materias_primas mp ON fti.materia_prima_id = mp.id 
                     JOIN fichas_tecnicas ft ON fti.ficha_tecnica_id = ft.id
                     WHERE ft.produto_modelo_id = :modelo_id",
                    ['modelo_id' => $modeloId]
                )['total'] ?? 0;

                $custoMO = Database::fetch(
                    "SELECT custo_mao_obra FROM fichas_tecnicas WHERE produto_modelo_id = :modelo_id",
                    ['modelo_id' => $modeloId]
                )['custo_mao_obra'] ?? 0;

                $custoTotalPeca = $custoMP + $custoMO;
                $valorPerdas += ($qtdPerda * $custoTotalPeca);
            }
        }

        // 8. Eficiência Produtiva
        $eficiencia = 100;
        $totalAmostra = $totalProduzido + $totalPerdido;
        if ($totalAmostra > 0) {
            $eficiencia = round(($totalProduzido / $totalAmostra) * 100, 1);
        }


        // --- LINHA 3: RECENTES ---
        // OPs Recentes
        $opsRecentes = Database::fetchAll(
            "SELECT op.*, pm.nome as modelo_nome, pm.referencia 
             FROM ordens_producao op 
             JOIN produtos_modelos pm ON op.produto_modelo_id = pm.id 
             WHERE op.tenant_id = :tenant_id 
             ORDER BY op.id DESC LIMIT 5",
            ['tenant_id' => $tenantId]
        );

        // Movimentações de Estoque Recentes
        $movimentacoesRecentes = Database::fetchAll(
            "SELECT em.*, u.nome as usuario_nome,
                    COALESCE(mp.nome, pm.nome) as item_nome
             FROM estoque_movimentacoes em 
             LEFT JOIN users u ON em.usuario_id = u.id 
             LEFT JOIN materias_primas mp ON em.tipo_item = 'materia_prima' AND em.item_id = mp.id
             LEFT JOIN produtos_modelos pm ON em.tipo_item = 'produto_acabado' AND em.item_id = pm.id
             WHERE em.tenant_id = :tenant_id 
             ORDER BY em.id DESC LIMIT 5",
            ['tenant_id' => $tenantId]
        );


        $dateFormat = Database::dateFormat('data_retorno', '%m/%Y');
        $producaoPeriodo = Database::fetchAll(
            "SELECT {$dateFormat} as periodo, 
                    SUM(quantidade_retornada_boa) as quantidade_produzida 
             FROM retornos_faccao 
             WHERE tenant_id = :tenant_id 
             GROUP BY periodo 
             ORDER BY MIN(data_retorno) DESC",
            ['tenant_id' => $tenantId]
        );

        // Renderizar a View do Dashboard
        $this->render('dashboard/index', [
            'title' => 'Dashboard Geral',
            'subtitle' => 'Métricas e acompanhamento da produção industrial',
            'modelosAtivos' => $modelosAtivos,
            'itensEstoque' => $itensEstoque,
            'opsAndamento' => $opsAndamento,
            'cargaFabrica' => $cargaFabrica,
            'valorEstoqueTotal' => $valorEstoqueTotal,
            'totalProduzido' => $totalProduzido,
            'totalPerdido' => $totalPerdido,
            'valorPerdas' => $valorPerdas,
            'eficiencia' => $eficiencia,
            'opsRecentes' => $opsRecentes,
            'movimentacoesRecentes' => $movimentacoesRecentes,
            'producaoPeriodo' => $producaoPeriodo
        ]);
    }
}
