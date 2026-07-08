<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - PCP Confecção</title>
    <link rel="stylesheet" href="/assets/css/main.css">
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
            max-width: 800px;
            margin: 0 auto;
            width: 100%;
        }
    </style>
</head>
<body>

    <header class="super-header">
        <div class="super-logo">
            <span>SA</span> PCP Confecção — Superadmin
        </div>
        <nav class="super-nav">
            <a href="/superadmin" class="active">Visão Geral</a>
            <a href="/superadmin/financeiro">Mensalidades</a>
            <a href="/logout" style="color: #f43f5e;"><i data-lucide="log-out" style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-right:4px;"></i>Sair</a>
        </nav>
    </header>

    <div class="super-container">
        
        <div class="page-header" style="border:none; margin-bottom: 20px;">
            <div>
                <a href="/superadmin" class="action-link" style="display:inline-flex; align-items:center; gap:5px; margin-bottom:10px;">
                    <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Voltar para Painel
                </a>
                <h1 class="page-title"><?= htmlspecialchars($title) ?></h1>
            </div>
        </div>

        <div class="card-box">
            <form action="<?= $action ?>" method="POST">
                <div class="sidebar-category" style="padding-left:0; margin-bottom: 12px; color: var(--primary);">Dados da Empresa</div>
                
                <div class="form-group">
                    <label for="nome" class="form-label">Nome da Empresa (Tenant) *</label>
                    <input type="text" id="nome" name="nome" class="form-control" value="<?= htmlspecialchars($tenant['nome'] ?? '') ?>" placeholder="Ex: Confecções Estilo S.A." required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="cnpj" class="form-label">CNPJ da Empresa *</label>
                        <input type="text" id="cnpj" name="cnpj" class="form-control" value="<?= htmlspecialchars($tenant['cnpj'] ?? '') ?>" placeholder="Ex: 00.000.000/0001-00" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="plano" class="form-label">Plano de Assinatura</label>
                        <select id="plano" name="plano" class="form-control">
                            <option value="Básico" <?= ($tenant['plano'] ?? '') === 'Básico' ? 'selected' : '' ?>>Básico</option>
                            <option value="Profissional" <?= ($tenant['plano'] ?? '') === 'Profissional' ? 'selected' : '' ?>>Profissional</option>
                            <option value="Premium" <?= ($tenant['plano'] ?? '') === 'Premium' ? 'selected' : '' ?>>Premium</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="mensalidade" class="form-label">Mensalidade (R$) *</label>
                        <input type="number" id="mensalidade" name="mensalidade" class="form-control" step="0.01" value="<?= htmlspecialchars($tenant['mensalidade'] ?? '0.00') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="status_pagamento" class="form-label">Status Pagamento</label>
                        <select id="status_pagamento" name="status_pagamento" class="form-control">
                            <option value="em dia" <?= ($tenant['status_pagamento'] ?? '') === 'em dia' ? 'selected' : '' ?>>Em Dia</option>
                            <option value="atrasado" <?= ($tenant['status_pagamento'] ?? '') === 'atrasado' ? 'selected' : '' ?>>Atrasado</option>
                            <option value="cancelado" <?= ($tenant['status_pagamento'] ?? '') === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                    </div>
                </div>

                <?php if ($tenant === null): ?>
                    <div class="sidebar-category" style="padding-left:0; margin-top:30px; margin-bottom: 12px; color: var(--primary);">Usuário Administrador da Empresa</div>
                    
                    <div class="form-group">
                        <label for="admin_nome" class="form-label">Nome Completo do Admin *</label>
                        <input type="text" id="admin_nome" name="admin_nome" class="form-control" placeholder="Ex: Carlos Silva" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="admin_email" class="form-label">E-mail do Admin *</label>
                            <input type="email" id="admin_email" name="admin_email" class="form-control" placeholder="Ex: admin@empresa.com" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_senha" class="form-label">Senha Inicial *</label>
                            <input type="password" id="admin_senha" name="admin_senha" class="form-control" placeholder="Mínimo 6 caracteres" required>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-actions">
                    <a href="/superadmin" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Salvar Empresa</button>
                </div>
            </form>
        </div>

    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
