<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Função auxiliar para marcar item ativo no menu
$isActive = function($path) use ($currentUri) {
    return $currentUri === $path ? 'active' : '';
};
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'C4 Smart') ?></title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="icon" type="image/png" href="https://i.ibb.co/zTGpC3HB/fav-icon-c4.png">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
<script>
    if (localStorage.getItem('sidebar-collapsed') === 'true') {
        document.body.classList.add('sidebar-collapsed');
    }
</script>


<?php if (isset($_SESSION['impersonate']) && $_SESSION['impersonate'] === true): ?>
    <div class="impersonation-banner">
        <span>Você está acessando como <strong><?= htmlspecialchars($_SESSION['tenant_nome'] ?? 'Cliente') ?></strong> (Modo Impersonation)</span>
        <a href="/superadmin/tenants/stop-impersonate">Voltar ao modo Superadmin</a>
    </div>
<?php endif; ?>

<div class="app-wrapper">
    <!-- Sidebar Fixa -->
    <aside class="sidebar">

        <div class="sidebar-header" style="justify-content: center; padding: 10px 15px; border-bottom: 1px solid #1e293b;">
            <img src="https://i.ibb.co/WN0MpYZJ/c4-smart-2.png" alt="C4 Smart" style="max-height: 110px; width: 100%; max-width: 200px; object-fit: contain;">
        </div>

        <nav class="sidebar-menu">
            <div class="sidebar-category">PAINEL</div>
            <a href="/dashboard" class="sidebar-item <?= $isActive('/dashboard') ?>">
                <i data-lucide="layout-dashboard"></i> Dashboard Geral
            </a>

            <div class="sidebar-category">CADASTROS</div>
            <a href="/produtos" class="sidebar-item <?= $isActive('/produtos') ?> <?= $isActive('/produtos/novo') ?> <?= $isActive('/produtos/editar') ?>">
                <i data-lucide="shirt"></i> Modelos de Produtos
            </a>
            <a href="/materias" class="sidebar-item <?= $isActive('/materias') ?> <?= $isActive('/materias/novo') ?> <?= $isActive('/materias/editar') ?>">
                <i data-lucide="scissors"></i> Matérias-Primas
            </a>
            <a href="/fichas" class="sidebar-item <?= $isActive('/fichas') ?> <?= $isActive('/fichas/novo') ?> <?= $isActive('/fichas/editar') ?>">
                <i data-lucide="file-text"></i> Fichas Técnicas
            </a>
            <a href="/oficinas" class="sidebar-item <?= $isActive('/oficinas') ?> <?= $isActive('/oficinas/novo') ?> <?= $isActive('/oficinas/editar') ?>">
                <i data-lucide="factory"></i> Oficinas / Facções
            </a>

            <div class="sidebar-category">COMERCIAL</div>
            <a href="/controle-custos" class="sidebar-item <?= $isActive('/controle-custos') ?>">
                <i data-lucide="dollar-sign"></i> Controle de Custos
            </a>
            <a href="/custos-industriais" class="sidebar-item <?= $isActive('/custos-industriais') ?> <?= $isActive('/custos-industriais/novo') ?>">
                <i data-lucide="building"></i> Custos Industriais
            </a>
            <a href="/pedidos" class="sidebar-item <?= $isActive('/pedidos') ?> <?= $isActive('/pedidos/novo') ?> <?= $isActive('/pedidos/editar') ?>">
                <i data-lucide="shopping-cart"></i> Pedidos de Venda
            </a>
            <a href="/financeiro-faccoes" class="sidebar-item <?= $isActive('/financeiro-faccoes') ?>">
                <i data-lucide="wallet"></i> Financeiro Facções
            </a>

            <div class="sidebar-category">PCP</div>
            <a href="/calendario" class="sidebar-item <?= $isActive('/calendario') ?>">
                <i data-lucide="calendar"></i> Calendário & Coleções
            </a>
            <a href="/ops" class="sidebar-item <?= $isActive('/ops') ?> <?= $isActive('/ops/novo') ?> <?= $isActive('/ops/editar') ?>">
                <i data-lucide="clipboard-list"></i> Ordens de Produção
            </a>
            <a href="/corte" class="sidebar-item <?= $isActive('/corte') ?> <?= $isActive('/corte/novo') ?>">
                <i data-lucide="slice"></i> Ordens de Corte
            </a>
            <a href="/chao-fabrica" class="sidebar-item <?= $isActive('/chao-fabrica') ?>">
                <i data-lucide="activity"></i> Chão de Fábrica
            </a>
            <a href="/retornos" class="sidebar-item <?= $isActive('/retornos') ?> <?= $isActive('/retornos/novo') ?>">
                <i data-lucide="undo-2"></i> Retornos de Facção
            </a>

            <div class="sidebar-category">ESTOQUE</div>
            <a href="/estoque/locais" class="sidebar-item <?= $isActive('/estoque/locais') ?>">
                <i data-lucide="warehouse"></i> Locais de Armazenagem
            </a>
            <a href="/estoque/ajuste" class="sidebar-item <?= $isActive('/estoque/ajuste') ?>">
                <i data-lucide="sliders"></i> Ajuste de Insumos
            </a>
            <a href="/produtos/estoque" class="sidebar-item <?= $isActive('/produtos/estoque') ?>">
                <i data-lucide="package"></i> Estoque de Acabados
            </a>


            <div class="sidebar-category">RELATÓRIOS</div>
            <a href="/relatorios/curva-abc" class="sidebar-item <?= $isActive('/relatorios/curva-abc') ?>">
                <i data-lucide="bar-chart-3"></i> Curva ABC
            </a>
            <a href="/relatorios/perdas" class="sidebar-item <?= $isActive('/relatorios/perdas') ?>">
                <i data-lucide="trending-down"></i> Relatório de Perdas
            </a>
            <a href="/relatorios/diagnostico-op" class="sidebar-item <?= $isActive('/relatorios/diagnostico-op') ?>">
                <i data-lucide="search-code"></i> Diagnóstico da OP
            </a>
            <a href="/relatorios/qualidade" class="sidebar-item <?= $isActive('/relatorios/qualidade') ?>">
                <i data-lucide="check-square"></i> Controle de Qualidade
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-profile-menu">
                <span class="user-profile-name" title="<?= htmlspecialchars($_SESSION['nome'] ?? 'Usuário') ?>"><?= htmlspecialchars($_SESSION['nome'] ?? 'Usuário') ?></span>
                <span class="user-profile-role"><?= strtoupper(htmlspecialchars($_SESSION['role'] ?? 'Operador')) ?></span>
                <a href="/logout" class="sidebar-item" style="padding: 4px 0; margin-top: 10px; color: #f43f5e;">
                    <i data-lucide="log-out" style="width:16px; height:16px;"></i> Sair do Sistema
                </a>
            </div>
        </div>
    </aside>

    <!-- Botão Flutuante de Colapso (Notion Style) fora da sidebar para evitar cortes de overflow -->
    <button type="button" id="sidebar-toggle-btn" class="sidebar-collapse-button" title="Esconder/Exibir Menu">
        <i data-lucide="chevron-left" id="sidebar-toggle-icon" style="width: 14px; height: 14px;"></i>
    </button>

    <!-- Conteúdo Principal -->
    <main class="main-content">
        <div class="page-header">
            <div>
                <h2 class="page-title"><?= htmlspecialchars($title ?? 'PCP') ?></h2>
                <?php if (isset($subtitle)): ?>
                    <p class="page-subtitle"><?= htmlspecialchars($subtitle) ?></p>
                <?php endif; ?>
            </div>
            <div class="header-meta">
                <span>Tenant ID: <strong class="tenant-badge">#<?= htmlspecialchars($_SESSION['tenant_id'] ?? 'N/A') ?></strong></span>
                <span>Data: <strong><?= date('d/m/Y') ?></strong></span>
            </div>
        </div>
        
        <?php
        // Mostrar Alertas Globais (Flash Messages) se houver
        $errorMsg = $_SESSION['flash']['error'] ?? null;
        $successMsg = $_SESSION['flash']['success'] ?? null;
        unset($_SESSION['flash']['error'], $_SESSION['flash']['success']);
        ?>

        <?php if ($errorMsg): ?>
            <div class="alert alert-danger">
                <i data-lucide="alert-circle" style="width:16px; height:16px;"></i>
                <?= htmlspecialchars($errorMsg) ?>
            </div>
        <?php endif; ?>

        <?php if ($successMsg): ?>
            <div class="alert alert-success">
                <i data-lucide="check-circle" style="width:16px; height:16px;"></i>
                <?= htmlspecialchars($successMsg) ?>
            </div>
        <?php endif; ?>
