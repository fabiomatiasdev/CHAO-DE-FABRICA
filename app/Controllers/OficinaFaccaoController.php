<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class OficinaFaccaoController extends Controller
{
    /**
     * Listar oficinas/facções.
     */
    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $busca    = trim($_GET['busca'] ?? '');
        $perPage  = 10;
        $page     = max(1, (int)($_GET['page'] ?? 1));

        $whereClause = 'WHERE tenant_id = :tenant_id';
        $params      = ['tenant_id' => $tenantId];

        if (!empty($busca)) {
            $whereClause .= ' AND (nome LIKE :busca OR cnpj_cpf LIKE :busca2 OR contato LIKE :busca3)';
            $params['busca']  = '%' . $busca . '%';
            $params['busca2'] = '%' . $busca . '%';
            $params['busca3'] = '%' . $busca . '%';
        }

        $total      = (int)(Database::fetch("SELECT COUNT(*) as total FROM oficinas_faccoes $whereClause", $params)['total'] ?? 0);
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $page       = max(1, min($page, $totalPages));
        $offset     = ($page - 1) * $perPage;

        $params['limit']  = $perPage;
        $params['offset'] = $offset;

        $oficinas = Database::fetchAll(
            "SELECT * FROM oficinas_faccoes $whereClause ORDER BY id DESC LIMIT :limit OFFSET :offset",
            $params
        );

        $this->render('oficinas/index', [
            'title'      => 'Oficinas / Facções',
            'subtitle'   => 'Cadastre e gerencie a capacidade produtiva e valores de costura das oficinas terceirizadas',
            'oficinas'   => $oficinas,
            'busca'      => $busca,
            'pagination' => ['total' => $total, 'perPage' => $perPage, 'currentPage' => $page, 'totalPages' => $totalPages]
        ]);
    }

    /**
     * Exibir formulário de cadastro.
     */
    public function create(): void
    {
        $this->render('oficinas/form', [
            'title' => 'Nova Oficina / Facção',
            'subtitle' => 'Cadastre as informações da nova oficina parceira',
            'oficina' => null,
            'action' => '/oficinas/novo'
        ]);
    }

    /**
     * Gravar nova oficina.
     */
    public function store(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $nome = trim($_POST['nome'] ?? '');
        $cnpj_cpf = trim($_POST['cnpj_cpf'] ?? '');
        $endereco = trim($_POST['endereco'] ?? '');
        $contato = trim($_POST['contato'] ?? '');
        $capacidade_produtiva = (int)($_POST['capacidade_produtiva'] ?? 0);
        $mao_obra_peca = (float)($_POST['mao_obra_peca'] ?? 0.00);
        $status = $_POST['status'] ?? 'ativo';

        if (empty($nome) || empty($cnpj_cpf)) {
            $this->setFlash('error', 'Nome e CNPJ/CPF são obrigatórios.');
            $this->redirect('/oficinas/novo');
        }

        try {
            Database::query(
                "INSERT INTO oficinas_faccoes (tenant_id, nome, cnpj_cpf, endereco, contato, capacidade_produtiva, mao_obra_peca, status) 
                 VALUES (:tenant_id, :nome, :cnpj_cpf, :endereco, :contato, :capacidade_produtiva, :mao_obra_peca, :status)",
                [
                    'tenant_id' => $tenantId,
                    'nome' => $nome,
                    'cnpj_cpf' => $cnpj_cpf,
                    'endereco' => $endereco,
                    'contato' => $contato,
                    'capacidade_produtiva' => $capacidade_produtiva,
                    'mao_obra_peca' => $mao_obra_peca,
                    'status' => $status
                ]
            );

            $this->setFlash('success', 'Oficina cadastrada com sucesso.');
            $this->redirect('/oficinas');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Erro ao cadastrar oficina: ' . $e->getMessage());
            $this->redirect('/oficinas/novo');
        }
    }

    /**
     * Exibir formulário de edição.
     */
    public function edit(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $id = (int)($_GET['id'] ?? 0);

        $oficina = Database::fetch(
            "SELECT * FROM oficinas_faccoes WHERE tenant_id = :tenant_id AND id = :id",
            ['tenant_id' => $tenantId, 'id' => $id]
        );

        if (!$oficina) {
            $this->setFlash('error', 'Oficina não encontrada.');
            $this->redirect('/oficinas');
        }

        $this->render('oficinas/form', [
            'title' => 'Editar Oficina / Facção',
            'subtitle' => "Modifique os dados da oficina {$oficina['nome']}",
            'oficina' => $oficina,
            'action' => "/oficinas/editar?id={$id}"
        ]);
    }

    /**
     * Atualizar oficina.
     */
    public function update(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $id = (int)($_GET['id'] ?? 0);

        $nome = trim($_POST['nome'] ?? '');
        $cnpj_cpf = trim($_POST['cnpj_cpf'] ?? '');
        $endereco = trim($_POST['endereco'] ?? '');
        $contato = trim($_POST['contato'] ?? '');
        $capacidade_produtiva = (int)($_POST['capacidade_produtiva'] ?? 0);
        $mao_obra_peca = (float)($_POST['mao_obra_peca'] ?? 0.00);
        $status = $_POST['status'] ?? 'ativo';

        if (empty($nome) || empty($cnpj_cpf)) {
            $this->setFlash('error', 'Preencha todos os campos obrigatórios.');
            $this->redirect("/oficinas/editar?id={$id}");
        }

        try {
            Database::query(
                "UPDATE oficinas_faccoes 
                 SET nome = :nome, cnpj_cpf = :cnpj_cpf, endereco = :endereco, contato = :contato, 
                     capacidade_produtiva = :capacidade_produtiva, mao_obra_peca = :mao_obra_peca, status = :status 
                 WHERE tenant_id = :tenant_id AND id = :id",
                [
                    'nome' => $nome,
                    'cnpj_cpf' => $cnpj_cpf,
                    'endereco' => $endereco,
                    'contato' => $contato,
                    'capacidade_produtiva' => $capacidade_produtiva,
                    'mao_obra_peca' => $mao_obra_peca,
                    'status' => $status,
                    'tenant_id' => $tenantId,
                    'id' => $id
                ]
            );

            $this->setFlash('success', 'Cadastro de oficina atualizado.');
            $this->redirect('/oficinas');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Erro ao atualizar: ' . $e->getMessage());
            $this->redirect("/oficinas/editar?id={$id}");
        }
    }

    /**
     * Excluir oficina.
     */
    public function delete(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $id = (int)($_GET['id'] ?? 0);

        try {
            Database::query(
                "DELETE FROM oficinas_faccoes WHERE tenant_id = :tenant_id AND id = :id",
                ['tenant_id' => $tenantId, 'id' => $id]
            );
            $this->setFlash('success', 'Oficina excluída com sucesso.');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Erro ao excluir oficina (verifique se existem OPs vinculadas a ela).');
        }

        $this->redirect('/oficinas');
    }

    /**
     * Controle Financeiro das Oficinas/Facções.
     */
    public function financeiro(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];

        $faturamentos = Database::fetchAll(
            "SELECT ff.*, of.nome as oficina_nome, op.id as op_id, pm.nome as modelo_nome
             FROM financeiro_faccoes ff
             JOIN oficinas_faccoes of ON ff.oficina_faccao_id = of.id
             LEFT JOIN retornos_faccao rf ON ff.retorno_faccao_id = rf.id
             LEFT JOIN ordens_producao op ON rf.ordem_producao_id = op.id
             LEFT JOIN produtos_modelos pm ON op.produto_modelo_id = pm.id
             WHERE ff.tenant_id = :tenant_id
             ORDER BY ff.id DESC",
            ['tenant_id' => $tenantId]
        );

        $this->render('oficinas/financeiro', [
            'title' => 'Financeiro de Oficinas / Facções',
            'subtitle' => 'Acompanhe e controle os pagamentos devidos por retorno de peças prontas',
            'faturamentos' => $faturamentos
        ]);
    }

    /**
     * Efetuar/Registrar pagamento para oficina.
     */
    public function pagarFinanceiro(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = $_SESSION['tenant_id'];
        $id = (int)($_POST['id'] ?? 0);
        $valorPago = (float)($_POST['valor_pago'] ?? 0.00);

        try {
            // Obter faturamento original
            $fin = Database::fetch(
                "SELECT * FROM financeiro_faccoes WHERE tenant_id = :tenant_id AND id = :id",
                ['tenant_id' => $tenantId, 'id' => $id]
            );

            if (!$fin) {
                $this->setFlash('error', 'Lançamento financeiro não encontrado.');
                $this->redirect('/financeiro-faccoes');
            }

            // Novo valor pago acumulado
            $novoValorPago = $fin['valor_pago'] + $valorPago;
            $status = ($novoValorPago >= $fin['valor_devido']) ? 'pago' : 'pendente';

            Database::query(
                "UPDATE financeiro_faccoes 
                 SET valor_pago = :valor_pago, status = :status 
                 WHERE tenant_id = :tenant_id AND id = :id",
                [
                    'valor_pago' => $novoValorPago,
                    'status' => $status,
                    'tenant_id' => $tenantId,
                    'id' => $id
                ]
            );

            $this->setFlash('success', 'Pagamento registrado com sucesso.');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Erro ao processar pagamento: ' . $e->getMessage());
        }

        $this->redirect('/financeiro-faccoes');
    }
}
