<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class CustosIndustriaisController extends Controller
{
    /**
     * Listar custos industriais.
     */
    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $mes = $_GET['mes'] ?? date('Y-m');

        $custos = Database::fetchAll(
            "SELECT * FROM custos_industriais 
             WHERE tenant_id = :tenant_id AND mes_referencia = :mes
             ORDER BY id DESC",
            ['tenant_id' => $tenantId, 'mes' => $mes]
        );

        $totalCustos = Database::fetch(
            "SELECT SUM(valor) as total FROM custos_industriais 
             WHERE tenant_id = :tenant_id AND mes_referencia = :mes",
            ['tenant_id' => $tenantId, 'mes' => $mes]
        )['total'] ?? 0;

        $this->render('custos/index', [
            'title' => 'Custos Industriais',
            'subtitle' => 'Gerencie custos fixos/indiretos de fábrica (energia, aluguel, administração) para rateio',
            'custos' => $custos,
            'mes' => $mes,
            'totalCustos' => $totalCustos
        ]);
    }

    /**
     * Gravar novo custo industrial.
     */
    public function store(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $descricao = trim($_POST['descricao'] ?? '');
        $valor = (float)($_POST['valor'] ?? 0.00);
        $tipo = $_POST['tipo'] ?? 'fixo';
        $mes_referencia = $_POST['mes_referencia'] ?? date('Y-m');

        if (empty($descricao) || $valor <= 0 || empty($mes_referencia)) {
            $this->setFlash('error', 'Preencha todos os campos corretamente (descrição, valor e mês).');
            $this->redirect('/custos-industriais?mes=' . $mes_referencia);
        }

        try {
            Database::query(
                "INSERT INTO custos_industriais (tenant_id, descricao, valor, tipo, mes_referencia) 
                 VALUES (:tenant_id, :descricao, :valor, :tipo, :mes_referencia)",
                [
                    'tenant_id' => $tenantId,
                    'descricao' => $descricao,
                    'valor' => $valor,
                    'tipo' => $tipo,
                    'mes_referencia' => $mes_referencia
                ]
            );

            $this->setFlash('success', 'Custo industrial lançado com sucesso.');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Erro ao lançar custo: ' . $e->getMessage());
        }

        $this->redirect('/custos-industriais?mes=' . $mes_referencia);
    }

    /**
     * Excluir custo industrial.
     */
    public function delete(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $id = (int)($_GET['id'] ?? 0);
        $mes = $_GET['mes'] ?? date('Y-m');

        try {
            Database::query(
                "DELETE FROM custos_industriais WHERE tenant_id = :tenant_id AND id = :id",
                ['tenant_id' => $tenantId, 'id' => $id]
            );
            $this->setFlash('success', 'Custo excluído.');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Erro ao excluir custo.');
        }

        $this->redirect('/custos-industriais?mes=' . $mes);
    }

    /**
     * Controle de Custos (Apuração por Modelo / Rateio)
     */
    public function controleCustos(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $mes = $_GET['mes'] ?? date('Y-m'); // Formato YYYY-MM

        // 1. Somar todos os custos industriais do mês
        $totalCustos = Database::fetch(
            "SELECT SUM(valor) as total FROM custos_industriais 
             WHERE tenant_id = :tenant_id AND mes_referencia = :mes",
            ['tenant_id' => $tenantId, 'mes' => $mes]
        )['total'] ?? 0;

        $dateFormat = Database::dateFormat('data_retorno', '%Y-%m');
        $totalProduzidoMes = Database::fetch(
            "SELECT SUM(quantidade_retornada_boa) as total 
             FROM retornos_faccao 
             WHERE tenant_id = :tenant_id AND {$dateFormat} = :mes",
            ['tenant_id' => $tenantId, 'mes' => $mes]
        )['total'] ?? 0;

        // 3. Rateio industrial por peça
        $custoIndustrialRateado = 0;
        if ($totalProduzidoMes > 0) {
            $custoIndustrialRateado = $totalCustos / $totalProduzidoMes;
        }

        // 4. Listar todos os modelos ativos para calcular o custo total de fabricação
        $modelos = Database::fetchAll(
            "SELECT pm.*, ft.id as ficha_id, ft.tempo_padrao, ft.custo_mao_obra
             FROM produtos_modelos pm
             LEFT JOIN fichas_tecnicas ft ON pm.id = ft.produto_modelo_id AND ft.tenant_id = :tenant_id2
             WHERE pm.tenant_id = :tenant_id AND pm.status = 'ativo'
             ORDER BY pm.referencia ASC",
            ['tenant_id' => $tenantId, 'tenant_id2' => $tenantId]
        );

        $apuracoes = [];
        foreach ($modelos as $m) {
            $custoMP = 0;
            if ($m['ficha_id']) {
                // Obter custo de matérias-primas
                $custoMP = Database::fetch(
                    "SELECT SUM(fti.quantidade * mp.custo_unitario) as total 
                     FROM fichas_tecnicas_itens fti 
                     JOIN materias_primas mp ON fti.materia_prima_id = mp.id 
                     WHERE fti.ficha_tecnica_id = :ficha_id",
                    ['ficha_id' => $m['ficha_id']]
                )['total'] ?? 0;
            }

            $maoObra = $m['custo_mao_obra'] ?? 0;
            $custoTotal = $custoMP + $maoObra + $custoIndustrialRateado;

            $apuracoes[] = [
                'referencia' => $m['referencia'],
                'nome' => $m['nome'],
                'cor' => $m['cor'],
                'possui_ficha' => !empty($m['ficha_id']),
                'custo_mp' => $custoMP,
                'custo_mo' => $maoObra,
                'custo_rateio' => $custoIndustrialRateado,
                'custo_total' => $custoTotal
            ];
        }

        $this->render('custos/controle', [
            'title' => 'Controle & Apuração de Custos',
            'subtitle' => 'Apuração detalhada de custo de fabricação por modelo de produto',
            'mes' => $mes,
            'totalCustos' => $totalCustos,
            'totalProduzidoMes' => $totalProduzidoMes,
            'custoRateadoPeca' => $custoIndustrialRateado,
            'apuracoes' => $apuracoes
        ]);
    }
}
