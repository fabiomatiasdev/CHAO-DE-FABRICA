<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class CalendarioController extends Controller
{
    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];

        $ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');
        $mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');

        if ($mes < 1) { $mes = 12; $ano--; }
        if ($mes > 12) { $mes = 1; $ano++; }

        $primeiroDia = sprintf('%04d-%02d-01', $ano, $mes);
        $diasNoMes = (int)date('t', strtotime($primeiroDia));
        $diaSemanaInicio = (int)date('w', strtotime($primeiroDia)); // 0 = Domingo

        // Buscar Coleções do Tenant
        $colecoes = Database::fetchAll(
            "SELECT c.*, 
                    (SELECT COUNT(*) FROM colecoes_ops co WHERE co.colecao_id = c.id) as total_ops
             FROM colecoes c 
             WHERE c.tenant_id = :tenant_id 
             ORDER BY c.data_inicio ASC",
            ['tenant_id' => $tenantId]
        );

        // Buscar OPs com prazo no mês
        $opsNoMes = Database::fetchAll(
            "SELECT op.id, op.quantidade, op.status, op.prazo, pm.nome as modelo_nome, pm.referencia
             FROM ordens_producao op
             JOIN produtos_modelos pm ON op.produto_modelo_id = pm.id
             WHERE op.tenant_id = :tenant_id 
               AND op.prazo >= :data_inicio AND op.prazo <= :data_fim
             ORDER BY op.prazo ASC",
            [
                'tenant_id'   => $tenantId,
                'data_inicio' => sprintf('%04d-%02d-01', $ano, $mes),
                'data_fim'    => sprintf('%04d-%02d-%02d', $ano, $mes, $diasNoMes)
            ]
        );

        // Buscar Tarefas do Mês
        $tarefasNoMes = Database::fetchAll(
            "SELECT * FROM cronograma_tarefas 
             WHERE tenant_id = :tenant_id 
               AND data_execucao >= :data_inicio AND data_execucao <= :data_fim
             ORDER BY data_execucao ASC",
            [
                'tenant_id'   => $tenantId,
                'data_inicio' => sprintf('%04d-%02d-01', $ano, $mes),
                'data_fim'    => sprintf('%04d-%02d-%02d', $ano, $mes, $diasNoMes)
            ]
        );

        // OPs disponíveis para vincular em novas coleções
        $todasOps = Database::fetchAll(
            "SELECT op.id, op.quantidade, pm.nome as modelo_nome, pm.referencia
             FROM ordens_producao op
             JOIN produtos_modelos pm ON op.produto_modelo_id = pm.id
             WHERE op.tenant_id = :tenant_id AND op.status != 'cancelada'
             ORDER BY op.id DESC",
            ['tenant_id' => $tenantId]
        );

        // Processar simulação de coleção selecionada (se houver param ?colecao_id=X)
        $simulacao = null;
        $colecaoSelecionada = null;
        if (isset($_GET['colecao_id']) && (int)$_GET['colecao_id'] > 0) {
            $colecaoId = (int)$_GET['colecao_id'];
            $colecaoSelecionada = Database::fetch(
                "SELECT * FROM colecoes WHERE id = :id AND tenant_id = :tenant_id",
                ['id' => $colecaoId, 'tenant_id' => $tenantId]
            );
            if ($colecaoSelecionada) {
                $simulacao = $this->calcularSimulacaoMRP($colecaoId, $tenantId);
            }
        }

        $this->render('calendario/index', [
            'title'              => 'Calendário de Coleções & Cronograma PCP',
            'subtitle'           => 'Agende coleções, monitore prazos e simule a disponibilidade de insumos (MRP)',
            'ano'                => $ano,
            'mes'                => $mes,
            'diasNoMes'          => $diasNoMes,
            'diaSemanaInicio'    => $diaSemanaInicio,
            'colecoes'           => $colecoes,
            'opsNoMes'           => $opsNoMes,
            'tarefasNoMes'       => $tarefasNoMes,
            'todasOps'           => $todasOps,
            'simulacao'          => $simulacao,
            'colecaoSelecionada' => $colecaoSelecionada
        ]);
    }

    public function storeColecao(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId   = $_SESSION['tenant_id'];
        $nome       = trim($_POST['nome'] ?? '');
        $descricao  = trim($_POST['descricao'] ?? '');
        $dataInicio = $_POST['data_inicio'] ?? '';
        $dataFim    = $_POST['data_fim'] ?? '';
        $opsIds     = $_POST['ops'] ?? [];

        if (empty($nome) || empty($dataInicio) || empty($dataFim)) {
            $_SESSION['flash_error'] = 'Preencha o nome e o período de início/fim da coleção.';
            header('Location: /calendario');
            exit;
        }

        Database::query(
            "INSERT INTO colecoes (tenant_id, nome, descricao, data_inicio, data_fim, status) 
             VALUES (:t, :n, :d, :di, :df, 'planejada')",
            [
                't'  => $tenantId,
                'n'  => $nome,
                'd'  => $descricao,
                'di' => $dataInicio,
                'df' => $dataFim
            ]
        );
        $colecaoId = (int)Database::lastInsertId();

        if (!empty($opsIds) && is_array($opsIds)) {
            foreach ($opsIds as $opId) {
                Database::query(
                    "INSERT INTO colecoes_ops (tenant_id, colecao_id, ordem_producao_id) 
                     VALUES (:t, :c, :op)",
                    ['t' => $tenantId, 'c' => $colecaoId, 'op' => (int)$opId]
                );
            }
        }

        $_SESSION['flash_success'] = "Coleção '{$nome}' agendada com sucesso!";
        header("Location: /calendario?colecao_id={$colecaoId}");
        exit;
    }

    public function excluirColecao(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId  = $_SESSION['tenant_id'];
        $colecaoId = (int)($_GET['id'] ?? 0);

        Database::query("DELETE FROM colecoes_ops WHERE colecao_id = :c AND tenant_id = :t", ['c' => $colecaoId, 't' => $tenantId]);
        Database::query("DELETE FROM colecoes WHERE id = :c AND tenant_id = :t", ['c' => $colecaoId, 't' => $tenantId]);

        $_SESSION['flash_success'] = 'Coleção removida com sucesso.';
        header('Location: /calendario');
        exit;
    }

    public function storeTarefa(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId     = $_SESSION['tenant_id'];
        $titulo       = trim($_POST['titulo'] ?? '');
        $descricao    = trim($_POST['descricao'] ?? '');
        $dataExecucao = $_POST['data_execucao'] ?? '';
        $colecaoId    = !empty($_POST['colecao_id']) ? (int)$_POST['colecao_id'] : null;
        $responsavel  = trim($_POST['responsavel'] ?? '');

        if (!empty($titulo) && !empty($dataExecucao)) {
            Database::query(
                "INSERT INTO cronograma_tarefas (tenant_id, colecao_id, titulo, descricao, data_execucao, responsavel)
                 VALUES (:t, :c, :tit, :d, :de, :r)",
                [
                    't'   => $tenantId,
                    'c'   => $colecaoId,
                    'tit' => $titulo,
                    'd'   => $descricao,
                    'de'  => $dataExecucao,
                    'r'   => $responsavel
                ]
            );
            $_SESSION['flash_success'] = 'Tarefa agendada no cronograma!';
        }

        header('Location: /calendario');
        exit;
    }

    public function concluirTarefa(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $id       = (int)($_GET['id'] ?? 0);

        Database::query(
            "UPDATE cronograma_tarefas SET status = 'concluido' WHERE id = :id AND tenant_id = :t",
            ['id' => $id, 't' => $tenantId]
        );

        header('Location: /calendario');
        exit;
    }

    public function excluirTarefa(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $id       = (int)($_GET['id'] ?? 0);

        Database::query("DELETE FROM cronograma_tarefas WHERE id = :id AND tenant_id = :t", ['id' => $id, 't' => $tenantId]);

        header('Location: /calendario');
        exit;
    }

    /**
     * Calcula a Simulação de Estoque / Explosão de Insumos (MRP) para a Coleção
     */
    private function calcularSimulacaoMRP(int $colecaoId, int $tenantId): array
    {
        $ops = Database::fetchAll(
            "SELECT op.*, pm.nome as modelo_nome, pm.referencia
             FROM colecoes_ops co
             JOIN ordens_producao op ON co.ordem_producao_id = op.id
             JOIN produtos_modelos pm ON op.produto_modelo_id = pm.id
             WHERE co.colecao_id = :c AND co.tenant_id = :t",
            ['c' => $colecaoId, 't' => $tenantId]
        );

        $necessidadeInsumos = [];
        $custoMO = 0.0;
        $totalPecas = 0;

        foreach ($ops as $op) {
            $totalPecas += (int)$op['quantidade'];
            $custoMO += ((float)($op['mao_obra_peca'] ?? 0)) * (int)$op['quantidade'];

            // Buscar itens da Ficha Técnica do Modelo
            $itensFicha = Database::fetchAll(
                "SELECT fti.*, mp.nome as materia_nome, mp.unidade_medida, mp.custo_unitario, mp.estoque_atual
                 FROM fichas_tecnicas ft
                 JOIN fichas_tecnicas_itens fti ON ft.id = fti.ficha_tecnica_id
                 JOIN materias_primas mp ON fti.materia_prima_id = mp.id
                 WHERE ft.produto_modelo_id = :pm_id AND ft.tenant_id = :t",
                ['pm_id' => $op['produto_modelo_id'], 't' => $tenantId]
            );

            foreach ($itensFicha as $item) {
                $mpId = (int)$item['materia_prima_id'];
                $qtdNecessaria = ((float)$item['quantidade']) * (int)$op['quantidade'];

                if (!isset($necessidadeInsumos[$mpId])) {
                    $necessidadeInsumos[$mpId] = [
                        'materia_prima_id' => $mpId,
                        'nome'             => $item['materia_nome'],
                        'unidade_medida'   => $item['unidade_medida'],
                        'custo_unitario'   => (float)$item['custo_unitario'],
                        'estoque_atual'    => (float)$item['estoque_atual'],
                        'qtd_necessaria'   => 0.0,
                    ];
                }
                $necessidadeInsumos[$mpId]['qtd_necessaria'] += $qtdNecessaria;
            }
        }

        $custoMP = 0.0;
        $insumosFaltantes = 0;
        $resultadoItens = [];

        foreach ($necessidadeInsumos as $ins) {
            $qtdNecessaria  = $ins['qtd_necessaria'];
            $estoqueAtual   = $ins['estoque_atual'];
            $custoItemTotal = $qtdNecessaria * $ins['custo_unitario'];
            $custoMP       += $custoItemTotal;

            $suficiente = $estoqueAtual >= $qtdNecessaria;
            $qtdFalta   = $suficiente ? 0.0 : ($qtdNecessaria - $estoqueAtual);

            if (!$suficiente) {
                $insumosFaltantes++;
            }

            $resultadoItens[] = array_merge($ins, [
                'custo_total'  => $custoItemTotal,
                'suficiente'   => $suficiente,
                'qtd_faltante' => $qtdFalta
            ]);
        }

        return [
            'ops'               => $ops,
            'total_ops'         => count($ops),
            'total_pecas'       => $totalPecas,
            'insumos'           => $resultadoItens,
            'insumos_faltantes' => $insumosFaltantes,
            'estoque_ok'        => $insumosFaltantes === 0,
            'custo_mp_total'    => $custoMP,
            'custo_mo_total'    => $custoMO,
            'custo_fabril_total'=> $custoMP + $custoMO
        ];
    }
}
