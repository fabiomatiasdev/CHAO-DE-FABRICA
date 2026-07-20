<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Superadmin - C4 Smart</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="icon" type="image/png" href="https://i.ibb.co/zTGpC3HB/fav-icon-c4.png">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .super-header {
            background-color: #0f172a;
            color: white;
            padding: 16px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .super-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 18px;
        }
        .super-logo span {
            background-color: var(--primary);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 14px;
        }
        .super-nav {
            display: flex;
            gap: 20px;
        }
        .super-nav a {
            color: #94a3b8;
            text-decoration: none;
            font-size: 14px;
            font-weight: 550;
            transition: color 0.2s;
        }
        .super-nav a:hover, .super-nav a.active {
            color: white;
        }
        .super-container {
            padding: 40px;
            max-width: 1300px;
            margin: 0 auto;
            width: 100%;
        }
    </style>
</head>
<body>

    <header class="super-header">
        <div class="super-logo">
            <img src="https://i.ibb.co/WN0MpYZJ/c4-smart-2.png" alt="C4 Smart" style="max-height: 35px; object-fit: contain;">
            <span style="font-size: 13px; background-color: var(--primary); padding: 2px 8px; border-radius: 4px; color: white;">Superadmin</span>
        </div>
        <nav class="super-nav">
            <a href="/superadmin" class="active">Visão Geral</a>
            <a href="/superadmin/financeiro">Mensalidades</a>
            <a href="/logout" style="color: #f43f5e;"><i data-lucide="log-out" style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-right:4px;"></i>Sair</a>
        </nav>
    </header>

    <div class="super-container">
        
        <?php
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $errorMsg = $_SESSION['flash']['error'] ?? null;
        $successMsg = $_SESSION['flash']['success'] ?? null;
        unset($_SESSION['flash']['error'], $_SESSION['flash']['success']);
        ?>

        <?php if ($errorMsg): ?>
            <div class="alert alert-danger" style="margin-bottom: 25px;">
                <i data-lucide="alert-circle"></i>
                <?= htmlspecialchars($errorMsg) ?>
            </div>
        <?php endif; ?>

        <?php if ($successMsg): ?>
            <div class="alert alert-success" style="margin-bottom: 25px;">
                <i data-lucide="check-circle"></i>
                <?= htmlspecialchars($successMsg) ?>
            </div>
        <?php endif; ?>

        <div class="page-header" style="border:none; margin-bottom: 20px;">
            <div>
                <h1 class="page-title">Gestão Global</h1>
                <p class="page-subtitle">Acompanhamento e controle dos tenants clientes do sistema</p>
            </div>
            <div>
                <a href="/superadmin/tenants/novo" class="btn btn-primary"><i data-lucide="plus"></i> Cadastrar Nova Empresa</a>
            </div>
        </div>

        <!-- Cards de Visão Geral -->
        <div class="dashboard-grid">
            <div class="stat-card stat-primary">
                <span class="stat-title">Empresas Cadastradas</span>
                <span class="stat-value"><?= $totalClientes ?></span>
                <span class="stat-subtext">Total de tenants ativos</span>
            </div>
            <div class="stat-card stat-success">
                <span class="stat-title">Faturamento Mensal Recebido</span>
                <span class="stat-value">R$ <?= number_format($totalReceitas, 2, ',', '.') ?></span>
                <span class="stat-subtext">Valores quitados</span>
            </div>
            <div class="stat-card stat-warning">
                <span class="stat-title">Valores Pendentes</span>
                <span class="stat-value">R$ <?= number_format($totalPendente, 2, ',', '.') ?></span>
                <span class="stat-subtext">Cobranças aguardando pagamento</span>
            </div>
        </div>

        <div class="layout-split" style="grid-template-columns: 2fr 1fr;">
            <!-- Lista de Tenants -->
            <div class="card-box">
                <div class="card-title-box">
                    <h3>Empresas Clientes</h3>
                </div>
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Empresa</th>
                                <th>CNPJ</th>
                                <th>Plano / Mensalidade</th>
                                <th>Pagamento</th>
                                <th style="text-align: right;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tenants)): ?>
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        <i data-lucide="building-2" style="width:32px;height:32px;"></i>
                                        <p>Nenhuma empresa cadastrada.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tenants as $t): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:600;"><?= htmlspecialchars($t['nome']) ?></div>
                                            <span style="font-size:11px;color:var(--muted);">Cadastrada em: <?= date('d/m/Y', strtotime($t['criado_em'])) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($t['cnpj']) ?></td>
                                        <td>
                                            <div><?= htmlspecialchars($t['plano']) ?></div>
                                            <span style="font-size:12px;color:var(--muted);">R$ <?= number_format($t['mensalidade'], 2, ',', '.') ?>/mês</span>
                                        </td>
                                        <td>
                                            <?php if ($t['status_pagamento'] === 'em dia'): ?>
                                                <span class="badge badge-success">Em Dia</span>
                                            <?php elseif ($t['status_pagamento'] === 'atrasado'): ?>
                                                <span class="badge badge-warning">Atrasado</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Cancelado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: right;" class="actions-cell">
                                            <a href="/superadmin/tenants/impersonate?id=<?= $t['id'] ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size:12px;" title="Acessar como admin desta empresa">
                                                <i data-lucide="eye" style="width:14px;height:14px;"></i> Acessar
                                            </a>
                                            <a href="/superadmin/tenants/editar?id=<?= $t['id'] ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size:12px;">
                                                <i data-lucide="edit-2" style="width:14px;height:14px;"></i> Editar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Auditoria / Acessos Recentes -->
            <div class="card-box">
                <div class="card-title-box">
                    <h3>Logs de Impersonação</h3>
                </div>
                <div style="display:flex; flex-direction:column; gap:16px;">
                    <?php if (empty($logs)): ?>
                        <div class="empty-state" style="padding: 20px 0;">
                            <i data-lucide="shield-alert" style="width:24px;height:24px;"></i>
                            <p>Nenhum log de acesso.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <div style="border-bottom: 1px solid var(--border); padding-bottom: 12px; font-size:13px;">
                                <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                                    <strong style="color:var(--primary);"><?= htmlspecialchars($log['superadmin_email']) ?></strong>
                                    <span style="font-size:11px; color:var(--muted);"><?= date('d/m H:i', strtotime($log['criado_em'])) ?></span>
                                </div>
                                <div style="color:#334155; margin-bottom: 2px;">
                                    <?= htmlspecialchars($log['acao']) ?>
                                </div>
                                <span style="font-size:11px; background-color:#f1f5f9; padding:2px 6px; border-radius:4px;">
                                    Empresa: <?= htmlspecialchars($log['tenant_nome']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
