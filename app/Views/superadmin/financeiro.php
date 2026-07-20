<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financeiro Clientes - C4 Smart</title>
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
            <a href="/superadmin">Visão Geral</a>
            <a href="/superadmin/financeiro" class="active">Mensalidades</a>
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
                <h1 class="page-title">Cobrança de Mensalidades</h1>
                <p class="page-subtitle">Acompanhe e controle os pagamentos mensais de assinaturas de cada empresa</p>
            </div>
        </div>

        <div class="card-box">
            <div class="card-title-box">
                <h3>Histórico de Cobranças</h3>
            </div>
            
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Empresa Cliente</th>
                            <th>Mês de Referência</th>
                            <th>Valor da Mensalidade</th>
                            <th>Status da Cobrança</th>
                            <th style="text-align: right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($historico)): ?>
                            <tr>
                                <td colspan="5" class="empty-state">
                                    <i data-lucide="dollar-sign" style="width:32px;height:32px;"></i>
                                    <p>Nenhuma cobrança registrada ainda.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($historico as $h): ?>
                                <tr>
                                    <td>
                                        <strong style="color: #0f172a;"><?= htmlspecialchars($h['tenant_nome']) ?></strong>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($h['mes_referencia']) ?></strong>
                                    </td>
                                    <td>R$ <?= number_format($h['valor'], 2, ',', '.') ?></td>
                                    <td>
                                        <?php if ($h['status_pagamento'] === 'pago'): ?>
                                            <span class="badge badge-success">Pago</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Aguardando Pagamento</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <form action="/superadmin/financeiro/pagamento" method="POST" style="display:inline-block;">
                                            <input type="hidden" name="cobranca_id" value="<?= $h['id'] ?>">
                                            <?php if ($h['status_pagamento'] === 'pendente'): ?>
                                                <input type="hidden" name="status" value="pago">
                                                <button type="submit" class="btn btn-success" style="padding: 6px 12px; font-size:12px;">
                                                    <i data-lucide="check" style="width:14px;height:14px;"></i> Marcar como Pago
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="status" value="pendente">
                                                <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; font-size:12px;">
                                                    <i data-lucide="x" style="width:14px;height:14px;"></i> Marcar como Pendente
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
