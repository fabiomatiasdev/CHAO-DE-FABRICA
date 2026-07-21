<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class RelatorioController extends Controller
{
    /**
     * Relatório: Curva ABC de Matérias-Primas.
     */
    public function curvaAbc(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];

        // Buscar itens ordenados pelo valor em estoque decrescente
        $itens = Database::fetchAll(
            "SELECT id, nome, unidade_medida, estoque_atual, custo_unitario,
                    (estoque_atual * custo_unitario) as valor_estoque
             FROM materias_primas 
             WHERE tenant_id = :tenant_id
             ORDER BY valor_estoque DESC",
            ['tenant_id' => $tenantId]
        );

        $valorEstoqueGlobal = 0;
        foreach ($itens as $it) {
            $valorEstoqueGlobal += $it['valor_estoque'];
        }

        $acumuladoValor = 0;
        $curva = [];

        foreach ($itens as $it) {
            $val = $it['valor_estoque'];
            $acumuladoValor += $val;
            
            $porcUnitario = $valorEstoqueGlobal > 0 ? ($val / $valorEstoqueGlobal) * 100 : 0;
            $porcAcumulada = $valorEstoqueGlobal > 0 ? ($acumuladoValor / $valorEstoqueGlobal) * 100 : 0;

            // Classificação ABC
            if ($porcAcumulada <= 70.01) {
                $classe = 'A';
            } elseif ($porcAcumulada <= 90.01) {
                $classe = 'B';
            } else {
                $classe = 'C';
            }

            $curva[] = [
                'nome' => $it['nome'],
                'unidade_medida' => $it['unidade_medida'],
                'estoque_atual' => $it['estoque_atual'],
                'custo_unitario' => $it['custo_unitario'],
                'valor_estoque' => $val,
                'porc_unitario' => $porcUnitario,
                'porc_acumulada' => $porcAcumulada,
                'classe' => $classe
            ];
        }

        $total      = count($curva);
        $perPage    = 10;
        $page       = max(1, (int)($_GET['page'] ?? 1));
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $page       = max(1, min($page, $totalPages));
        $offset     = ($page - 1) * $perPage;

        $curvaPaginada = array_slice($curva, $offset, $perPage);

        $this->render('relatorios/curva_abc', [
            'title'            => 'Curva ABC de Matérias-Primas',
            'subtitle'         => 'Classificação de relevância do estoque por valor financeiro acumulado',
            'curva'            => $curvaPaginada,
            'valorTotalGlobal' => $valorEstoqueGlobal,
            'pagination'       => ['total' => $total, 'perPage' => $perPage, 'currentPage' => $page, 'totalPages' => $totalPages]
        ]);
    }

    /**
     * Relatório: Relatório de Perdas de Produção.
     */
    public function perdas(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];

        $mes = $_GET['mes'] ?? '';
        $modeloId = !empty($_GET['modelo_id']) ? (int)$_GET['modelo_id'] : null;
        $oficinaId = !empty($_GET['oficina_id']) ? (int)$_GET['oficina_id'] : null;

        // Montar query dinâmica
        $sql = "SELECT rf.quantidade_defeito_perda, rf.data_retorno, op.id as op_id, op.produto_modelo_id,
                       pm.nome as modelo_nome, pm.referencia, of.nome as oficina_nome
                FROM retornos_faccao rf
                JOIN ordens_producao op ON rf.ordem_producao_id = op.id
                JOIN produtos_modelos pm ON op.produto_modelo_id = pm.id
                LEFT JOIN oficinas_faccoes of ON op.oficina_faccao_id = of.id
                WHERE rf.tenant_id = :tenant_id";

        $params = ['tenant_id' => $tenantId];

        if (!empty($mes)) {
            $dateFormat = Database::dateFormat('rf.data_retorno', '%Y-%m');
            $sql .= " AND {$dateFormat} = :mes";
            $params['mes'] = $mes;
        }
        if ($modeloId) {
            $sql .= " AND op.produto_modelo_id = :modelo_id";
            $params['modelo_id'] = $modeloId;
        }
        if ($oficinaId) {
            $sql .= " AND op.oficina_faccao_id = :oficina_id";
            $params['oficina_id'] = $oficinaId;
        }

        $sql .= " ORDER BY rf.id DESC";

        $perdasBrutas = Database::fetchAll($sql, $params);
        $perdasProcessadas = [];
        $totalPecasPerdidas = 0;
        $totalValorPerdido = 0;

        foreach ($perdasBrutas as $p) {
            $mId = $p['produto_modelo_id'];
            
            // Custo da peça a partir de sua Ficha Técnica (MP + MO)
            $custoMP = Database::fetch(
                "SELECT SUM(fti.quantidade * mp.custo_unitario) as total 
                 FROM fichas_tecnicas_itens fti 
                 JOIN materias_primas mp ON fti.materia_prima_id = mp.id 
                 JOIN fichas_tecnicas ft ON fti.ficha_tecnica_id = ft.id
                 WHERE ft.produto_modelo_id = :modelo_id",
                ['modelo_id' => $mId]
            )['total'] ?? 0;

            $custoMO = Database::fetch(
                "SELECT custo_mao_obra FROM fichas_tecnicas WHERE produto_modelo_id = :modelo_id",
                ['modelo_id' => $mId]
            )['custo_mao_obra'] ?? 0;

            $custoTotalPeca = $custoMP + $custoMO;
            $valorPerdaTotal = $p['quantidade_defeito_perda'] * $custoTotalPeca;

            $totalPecasPerdidas += $p['quantidade_defeito_perda'];
            $totalValorPerdido += $valorPerdaTotal;

            $perdasProcessadas[] = [
                'op_id' => $p['op_id'],
                'referencia' => $p['referencia'],
                'modelo_nome' => $p['modelo_nome'],
                'oficina_nome' => $p['oficina_nome'] ?: 'Interno',
                'quantidade_perdida' => $p['quantidade_defeito_perda'],
                'custo_unitario' => $custoTotalPeca,
                'valor_perdido' => $valorPerdaTotal,
                'data_retorno' => $p['data_retorno']
            ];
        }

        // Listas auxiliares para os selects de filtros
        $modelos  = Database::fetchAll("SELECT id, nome, referencia FROM produtos_modelos WHERE tenant_id = :tenant_id", ['tenant_id' => $tenantId]);
        $oficinas = Database::fetchAll("SELECT id, nome FROM oficinas_faccoes WHERE tenant_id = :tenant_id", ['tenant_id' => $tenantId]);

        $total      = count($perdasProcessadas);
        $perPage    = 10;
        $page       = max(1, (int)($_GET['page'] ?? 1));
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $page       = max(1, min($page, $totalPages));
        $offset     = ($page - 1) * $perPage;

        $perdasPaginadas = array_slice($perdasProcessadas, $offset, $perPage);

        $this->render('relatorios/perdas', [
            'title'         => 'Relatório de Perdas da Produção',
            'subtitle'      => 'Apuração quantitativa e financeira de defeitos e perdas industriais',
            'perdas'        => $perdasPaginadas,
            'totalPecas'    => $totalPecasPerdidas,
            'totalValor'    => $totalValorPerdido,
            'modelos'       => $modelos,
            'oficinas'      => $oficinas,
            'filtroMes'     => $mes,
            'filtroModelo'  => $modeloId,
            'filtroOficina' => $oficinaId,
            'pagination'    => ['total' => $total, 'perPage' => $perPage, 'currentPage' => $page, 'totalPages' => $totalPages]
        ]);
    }

    /**
     * Relatório: Diagnóstico de Ordem de Produção (Previsto x Realizado)
     */
    public function diagnosticoOp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $opId = (int)($_GET['op_id'] ?? 0);

        // Listar OPs para select box
        $ops = Database::fetchAll(
            "SELECT op.id, pm.referencia, pm.nome as modelo_nome
             FROM ordens_producao op
             JOIN produtos_modelos pm ON op.produto_modelo_id = pm.id
             WHERE op.tenant_id = :tenant_id
             ORDER BY op.id DESC",
            ['tenant_id' => $tenantId]
        );

        $diagnostico = null;

        if ($opId > 0) {
            $op = Database::fetch(
                "SELECT op.*, pm.nome as modelo_nome, pm.referencia, pm.cor, of.nome as oficina_nome, of.mao_obra_peca
                 FROM ordens_producao op
                 JOIN produtos_modelos pm ON op.produto_modelo_id = pm.id
                 LEFT JOIN oficinas_faccoes of ON op.oficina_faccao_id = of.id
                 WHERE op.tenant_id = :tenant_id AND op.id = :id",
                ['tenant_id' => $tenantId, 'id' => $opId]
            );

            if ($op) {
                // Obter consumo e custos da Ficha Técnica
                $custoMP = Database::fetch(
                    "SELECT SUM(fti.quantidade * mp.custo_unitario) as total 
                     FROM fichas_tecnicas_itens fti 
                     JOIN materias_primas mp ON fti.materia_prima_id = mp.id 
                     JOIN fichas_tecnicas ft ON fti.ficha_tecnica_id = ft.id
                     WHERE ft.produto_modelo_id = :modelo_id",
                    ['modelo_id' => $op['produto_modelo_id']]
                )['total'] ?? 0;

                $tempoUnitario = Database::fetch(
                    "SELECT tempo_padrao FROM fichas_tecnicas WHERE produto_modelo_id = :modelo_id",
                    ['modelo_id' => $op['produto_modelo_id']]
                )['tempo_padrao'] ?? 0;

                // Buscar retornos ocorridos para esta OP
                $retornos = Database::fetch(
                    "SELECT SUM(quantidade_retornada_boa) as boa, SUM(quantidade_defeito_perda) as perda
                     FROM retornos_faccao 
                     WHERE ordem_producao_id = :op_id",
                    ['op_id' => $opId]
                );

                $realizadoBoa = $retornos['boa'] ?? 0;
                $realizadoPerda = $retornos['perda'] ?? 0;

                // Eficiência do Lote
                $eficiencia = 0;
                if ($op['quantidade'] > 0) {
                    $eficiencia = round(($realizadoBoa / $op['quantidade']) * 100, 1);
                }

                // Custo de mão de obra real pago à oficina
                $custoMaoObraTotal = $realizadoBoa * ($op['mao_obra_peca'] ?? 0.00);

                // Custo de Matéria-Prima total estimado
                $custoMPTotal = $op['quantidade'] * $custoMP;

                // Perda financeira
                $custoTotalPeca = $custoMP + ($op['mao_obra_peca'] ?? 0.00);
                $perdaFinanceira = $realizadoPerda * $custoTotalPeca;

                // Tempo total de fabricação estimado
                $tempoTotalMinutos = $realizadoBoa * $tempoUnitario;

                $diagnostico = [
                    'op' => $op,
                    'previsto' => $op['quantidade'],
                    'realizado_boa' => $realizadoBoa,
                    'realizado_perda' => $realizadoPerda,
                    'eficiencia' => $eficiencia,
                    'custo_unitario_mp' => $custoMP,
                    'custo_mp_total' => $custoMPTotal,
                    'custo_mo_total' => $custoMaoObraTotal,
                    'perda_financeira' => $perdaFinanceira,
                    'tempo_total' => $tempoTotalMinutos,
                    'tempo_efetivo' => $tempoTotalMinutos / ($op['operadores'] ?: 1)
                ];
            }
        }

        $this->render('relatorios/diagnostico_op', [
            'title' => 'Diagnóstico Detalhado da OP',
            'subtitle' => 'Visão analítica de custos, eficiência e perdas para uma OP específica',
            'ops' => $ops,
            'selectedOpId' => $opId,
            'diagnostico' => $diagnostico
        ]);
    }

    /**
     * Relatório/Módulo: Controle de Qualidade.
     */
    public function qualidade(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $perPage  = 10;
        $page     = max(1, (int)($_GET['page'] ?? 1));

        $total = (int)(Database::fetch(
            "SELECT COUNT(*) as total FROM controle_qualidade WHERE tenant_id = :tenant_id",
            ['tenant_id' => $tenantId]
        )['total'] ?? 0);
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $page       = max(1, min($page, $totalPages));
        $offset     = ($page - 1) * $perPage;

        // Buscar inspeções registradas
        $inspecoes = Database::fetchAll(
            "SELECT cq.*, op.id as op_id, pm.nome as modelo_nome, pm.referencia
             FROM controle_qualidade cq
             JOIN ordens_producao op ON cq.ordem_producao_id = op.id
             JOIN produtos_modelos pm ON op.produto_modelo_id = pm.id
             WHERE cq.tenant_id = :tenant_id
             ORDER BY cq.id DESC LIMIT :limit OFFSET :offset",
            ['tenant_id' => $tenantId, 'limit' => $perPage, 'offset' => $offset]
        );

        // OPs disponíveis para inspeção (ativas ou concluídas recentemente)
        $ops = Database::fetchAll(
            "SELECT op.id, pm.nome as modelo_nome, pm.referencia
             FROM ordens_producao op
             JOIN produtos_modelos pm ON op.produto_modelo_id = pm.id
             WHERE op.tenant_id = :tenant_id AND op.status != 'cancelada'
             ORDER BY op.id DESC",
            ['tenant_id' => $tenantId]
        );

        $this->render('relatorios/qualidade', [
            'title'      => 'Controle de Qualidade',
            'subtitle'   => 'Registre inspeções técnicas e catalogue tipos de defeitos encontrados nas peças',
            'inspecoes'  => $inspecoes,
            'ops'        => $ops,
            'pagination' => ['total' => $total, 'perPage' => $perPage, 'currentPage' => $page, 'totalPages' => $totalPages]
        ]);
    }


    /**
     * Gravar nova inspeção de qualidade.
     */
    public function qualidadeSalvar(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $ordem_producao_id = (int)($_POST['ordem_producao_id'] ?? 0);
        $quantidade_aprovada = (int)($_POST['quantidade_aprovada'] ?? 0);
        $quantidade_reprovada = (int)($_POST['quantidade_reprovada'] ?? 0);
        $tipo_defeito = trim($_POST['tipo_defeito'] ?? '');
        $responsavel = trim($_POST['responsavel'] ?? '');

        if ($ordem_producao_id <= 0 || $quantidade_aprovada < 0 || $quantidade_reprovada < 0 || empty($responsavel)) {
            $this->setFlash('error', 'Preencha todos os campos obrigatórios corretamente.');
            $this->redirect('/relatorios/qualidade');
        }

        try {
            Database::query(
                "INSERT INTO controle_qualidade (tenant_id, ordem_producao_id, quantidade_aprovada, quantidade_reprovada, tipo_defeito, responsavel) 
                 VALUES (:tenant_id, :ordem_producao_id, :quantidade_aprovada, :quantidade_reprovada, :tipo_defeito, :responsavel)",
                [
                    'tenant_id' => $tenantId,
                    'ordem_producao_id' => $ordem_producao_id,
                    'quantidade_aprovada' => $quantidade_aprovada,
                    'quantidade_reprovada' => $quantidade_reprovada,
                    'tipo_defeito' => $tipo_defeito,
                    'responsavel' => $responsavel
                ]
            );

            $this->setFlash('success', 'Inspeção de qualidade registrada com sucesso.');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Erro ao salvar inspeção: ' . $e->getMessage());
        }

        $this->redirect('/relatorios/qualidade');
    }
}
