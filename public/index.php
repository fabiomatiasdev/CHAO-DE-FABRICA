<?php

// Habilitar exibição de erros durante o desenvolvimento
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Detecta automaticamente o caminho base
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    $basePath = __DIR__ . '/..'       // Local (pasta public/ separada)
;
} elseif (file_exists(__DIR__ . '/vendor/autoload.php')) {
    $basePath = __DIR__;              // Produção (tudo em pcp/)
} else {
    die('vendor/autoload.php não encontrado. Faça o upload da pasta vendor/.');
}

require_once $basePath . '/vendor/autoload.php';

// Carregar variáveis de ambiente do arquivo .env
if (file_exists($basePath . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable($basePath);
    $dotenv->load();
}

// Inicializar Sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Core\Router;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\AdminMiddleware;
use App\Middlewares\SuperadminMiddleware;

$router = new Router();

// Rota raiz redireciona para dashboard se logado, senão para login
$router->get('/', [App\Controllers\DashboardController::class, 'root']);

// Autenticação
$router->get('/login', [App\Controllers\AuthController::class, 'showLogin']);
$router->post('/login', [App\Controllers\AuthController::class, 'login']);
$router->get('/logout', [App\Controllers\AuthController::class, 'logout']);

// Rotas do Superadmin
$router->get('/superadmin', [App\Controllers\SuperadminController::class, 'dashboard'], [SuperadminMiddleware::class]);
$router->get('/superadmin/tenants', [App\Controllers\SuperadminController::class, 'listTenants'], [SuperadminMiddleware::class]);
$router->get('/superadmin/tenants/novo', [App\Controllers\SuperadminController::class, 'newTenant'], [SuperadminMiddleware::class]);
$router->post('/superadmin/tenants/novo', [App\Controllers\SuperadminController::class, 'createTenant'], [SuperadminMiddleware::class]);
$router->get('/superadmin/tenants/editar', [App\Controllers\SuperadminController::class, 'editTenant'], [SuperadminMiddleware::class]);
$router->post('/superadmin/tenants/editar', [App\Controllers\SuperadminController::class, 'updateTenant'], [SuperadminMiddleware::class]);
$router->get('/superadmin/tenants/impersonate', [App\Controllers\SuperadminController::class, 'impersonate'], [SuperadminMiddleware::class]);
$router->get('/superadmin/tenants/stop-impersonate', [App\Controllers\SuperadminController::class, 'stopImpersonate'], [AuthMiddleware::class]);
$router->get('/superadmin/financeiro', [App\Controllers\SuperadminController::class, 'financeiro'], [SuperadminMiddleware::class]);
$router->post('/superadmin/financeiro/pagamento', [App\Controllers\SuperadminController::class, 'registrarPagamento'], [SuperadminMiddleware::class]);

// Rotas Operacionais (Multi-tenant)
$router->get('/dashboard', [App\Controllers\DashboardController::class, 'index'], [AuthMiddleware::class]);

// Modelos de Produtos
$router->get('/produtos', [App\Controllers\ProdutoModeloController::class, 'index'], [AuthMiddleware::class]);
$router->get('/produtos/novo', [App\Controllers\ProdutoModeloController::class, 'create'], [AuthMiddleware::class]);
$router->post('/produtos/novo', [App\Controllers\ProdutoModeloController::class, 'store'], [AuthMiddleware::class]);
$router->get('/produtos/editar', [App\Controllers\ProdutoModeloController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/produtos/editar', [App\Controllers\ProdutoModeloController::class, 'update'], [AuthMiddleware::class]);
$router->get('/produtos/excluir', [App\Controllers\ProdutoModeloController::class, 'delete'], [AuthMiddleware::class]);

// Matérias-Primas
$router->get('/materias', [App\Controllers\MateriaPrimaController::class, 'index'], [AuthMiddleware::class]);
$router->get('/materias/novo', [App\Controllers\MateriaPrimaController::class, 'create'], [AuthMiddleware::class]);
$router->post('/materias/novo', [App\Controllers\MateriaPrimaController::class, 'store'], [AuthMiddleware::class]);
$router->get('/materias/editar', [App\Controllers\MateriaPrimaController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/materias/editar', [App\Controllers\MateriaPrimaController::class, 'update'], [AuthMiddleware::class]);
$router->get('/materias/excluir', [App\Controllers\MateriaPrimaController::class, 'delete'], [AuthMiddleware::class]);

// Fichas Técnicas
$router->get('/fichas', [App\Controllers\FichaTecnicaController::class, 'index'], [AuthMiddleware::class]);
$router->get('/fichas/novo', [App\Controllers\FichaTecnicaController::class, 'create'], [AuthMiddleware::class]);
$router->post('/fichas/novo', [App\Controllers\FichaTecnicaController::class, 'store'], [AuthMiddleware::class]);
$router->get('/fichas/editar', [App\Controllers\FichaTecnicaController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/fichas/editar', [App\Controllers\FichaTecnicaController::class, 'update'], [AuthMiddleware::class]);
$router->get('/fichas/excluir', [App\Controllers\FichaTecnicaController::class, 'delete'], [AuthMiddleware::class]);

// Oficinas / Facções
$router->get('/oficinas', [App\Controllers\OficinaFaccaoController::class, 'index'], [AuthMiddleware::class]);
$router->get('/oficinas/novo', [App\Controllers\OficinaFaccaoController::class, 'create'], [AuthMiddleware::class]);
$router->post('/oficinas/novo', [App\Controllers\OficinaFaccaoController::class, 'store'], [AuthMiddleware::class]);
$router->get('/oficinas/editar', [App\Controllers\OficinaFaccaoController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/oficinas/editar', [App\Controllers\OficinaFaccaoController::class, 'update'], [AuthMiddleware::class]);
$router->get('/oficinas/excluir', [App\Controllers\OficinaFaccaoController::class, 'delete'], [AuthMiddleware::class]);

// Comercial - Pedidos de Venda
$router->get('/pedidos', [App\Controllers\PedidoVendaController::class, 'index'], [AuthMiddleware::class]);
$router->get('/pedidos/novo', [App\Controllers\PedidoVendaController::class, 'create'], [AuthMiddleware::class]);
$router->post('/pedidos/novo', [App\Controllers\PedidoVendaController::class, 'store'], [AuthMiddleware::class]);
$router->get('/pedidos/editar', [App\Controllers\PedidoVendaController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/pedidos/editar', [App\Controllers\PedidoVendaController::class, 'update'], [AuthMiddleware::class]);
$router->get('/pedidos/excluir', [App\Controllers\PedidoVendaController::class, 'delete'], [AuthMiddleware::class]);

// Comercial - Custos Industriais
$router->get('/custos-industriais', [App\Controllers\CustosIndustriaisController::class, 'index'], [AuthMiddleware::class]);
$router->get('/custos-industriais/novo', [App\Controllers\CustosIndustriaisController::class, 'create'], [AuthMiddleware::class]);
$router->post('/custos-industriais/novo', [App\Controllers\CustosIndustriaisController::class, 'store'], [AuthMiddleware::class]);
$router->get('/custos-industriais/excluir', [App\Controllers\CustosIndustriaisController::class, 'delete'], [AuthMiddleware::class]);

// Comercial - Controle de Custos (Apuração por Modelo)
$router->get('/controle-custos', [App\Controllers\CustosIndustriaisController::class, 'controleCustos'], [AuthMiddleware::class]);

// Comercial - Financeiro Facções
$router->get('/financeiro-faccoes', [App\Controllers\OficinaFaccaoController::class, 'financeiro'], [AuthMiddleware::class]);
$router->post('/financeiro-faccoes/pagar', [App\Controllers\OficinaFaccaoController::class, 'pagarFinanceiro'], [AuthMiddleware::class]);

// PCP - Ordens de Produção
$router->get('/ops', [App\Controllers\OrdemProducaoController::class, 'index'], [AuthMiddleware::class]);
$router->get('/ops/novo', [App\Controllers\OrdemProducaoController::class, 'create'], [AuthMiddleware::class]);
$router->post('/ops/novo', [App\Controllers\OrdemProducaoController::class, 'store'], [AuthMiddleware::class]);
$router->get('/ops/editar', [App\Controllers\OrdemProducaoController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/ops/editar', [App\Controllers\OrdemProducaoController::class, 'update'], [AuthMiddleware::class]);
$router->get('/ops/excluir', [App\Controllers\OrdemProducaoController::class, 'delete'], [AuthMiddleware::class]);

// PCP - Calendário de Coleções & Cronograma
$router->get('/calendario', [App\Controllers\CalendarioController::class, 'index'], [AuthMiddleware::class]);
$router->post('/colecoes/novo', [App\Controllers\CalendarioController::class, 'storeColecao'], [AuthMiddleware::class]);
$router->get('/colecoes/excluir', [App\Controllers\CalendarioController::class, 'excluirColecao'], [AuthMiddleware::class]);
$router->post('/tarefas/novo', [App\Controllers\CalendarioController::class, 'storeTarefa'], [AuthMiddleware::class]);
$router->get('/tarefas/alternar', [App\Controllers\CalendarioController::class, 'alternarStatusTarefa'], [AuthMiddleware::class]);
$router->get('/tarefas/concluir', [App\Controllers\CalendarioController::class, 'concluirTarefa'], [AuthMiddleware::class]);
$router->get('/tarefas/excluir', [App\Controllers\CalendarioController::class, 'excluirTarefa'], [AuthMiddleware::class]);

// PCP - Ordens de Corte
$router->get('/corte', [App\Controllers\OrdemCorteController::class, 'index'], [AuthMiddleware::class]);
$router->get('/corte/novo', [App\Controllers\OrdemCorteController::class, 'create'], [AuthMiddleware::class]);
$router->post('/corte/novo', [App\Controllers\OrdemCorteController::class, 'store'], [AuthMiddleware::class]);
$router->get('/corte/excluir', [App\Controllers\OrdemCorteController::class, 'delete'], [AuthMiddleware::class]);

// PCP - Chão de Fábrica
$router->get('/chao-fabrica', [App\Controllers\ChaoFabricaController::class, 'index'], [AuthMiddleware::class]);
$router->post('/chao-fabrica/apontar', [App\Controllers\ChaoFabricaController::class, 'apontar'], [AuthMiddleware::class]);

// PCP - Retornos e Envios para Facção
$router->get('/retornos', [App\Controllers\RetornoFaccaoController::class, 'index'], [AuthMiddleware::class]);
$router->get('/retornos/envio', [App\Controllers\RetornoFaccaoController::class, 'envioForm'], [AuthMiddleware::class]);
$router->post('/retornos/envio', [App\Controllers\RetornoFaccaoController::class, 'storeEnvio'], [AuthMiddleware::class]);
$router->get('/retornos/novo', [App\Controllers\RetornoFaccaoController::class, 'create'], [AuthMiddleware::class]);
$router->post('/retornos/novo', [App\Controllers\RetornoFaccaoController::class, 'store'], [AuthMiddleware::class]);


// Estoque - Ajuste e Locais
$router->get('/estoque/ajuste', [App\Controllers\EstoqueController::class, 'ajuste'], [AuthMiddleware::class]);
$router->post('/estoque/ajuste', [App\Controllers\EstoqueController::class, 'processarAjuste'], [AuthMiddleware::class]);
$router->get('/estoque/locais', [App\Controllers\EstoqueLocalController::class, 'index'], [AuthMiddleware::class]);
$router->post('/estoque/locais/novo', [App\Controllers\EstoqueLocalController::class, 'store'], [AuthMiddleware::class]);
$router->post('/estoque/locais/editar', [App\Controllers\EstoqueLocalController::class, 'update'], [AuthMiddleware::class]);
$router->get('/estoque/locais/excluir', [App\Controllers\EstoqueLocalController::class, 'delete'], [AuthMiddleware::class]);

// Estoque de Produtos Acabados (Variantes)
$router->get('/produtos/estoque', [App\Controllers\ProdutoModeloController::class, 'estoque'], [AuthMiddleware::class]);
$router->post('/produtos/estoque/ajuste', [App\Controllers\ProdutoModeloController::class, 'ajustarEstoque'], [AuthMiddleware::class]);

// Relatórios
$router->get('/relatorios/curva-abc', [App\Controllers\RelatorioController::class, 'curvaAbc'], [AuthMiddleware::class]);
$router->get('/relatorios/perdas', [App\Controllers\RelatorioController::class, 'perdas'], [AuthMiddleware::class]);
$router->get('/relatorios/diagnostico-op', [App\Controllers\RelatorioController::class, 'diagnosticoOp'], [AuthMiddleware::class]);
$router->get('/relatorios/qualidade', [App\Controllers\RelatorioController::class, 'qualidade'], [AuthMiddleware::class]);
$router->post('/relatorios/qualidade/novo', [App\Controllers\RelatorioController::class, 'qualidadeSalvar'], [AuthMiddleware::class]);

// Despachar Rota
$router->dispatch();
