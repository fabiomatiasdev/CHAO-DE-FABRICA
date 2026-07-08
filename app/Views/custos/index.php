<?php require __DIR__ . '/../layouts/header.php'; ?>

<!-- Filtro de Mês -->
<div class="card-box" style="margin-bottom: 20px;">
    <form action="/custos-industriais" method="GET" style="display: flex; gap: 15px; align-items: flex-end;">
        <div class="form-group" style="margin-bottom:0;">
            <label for="mes" class="form-label" style="margin-bottom:4px;">Selecione o Mês de Referência</label>
            <input type="month" id="mes" name="mes" class="form-control" value="<?= htmlspecialchars($mes) ?>" style="max-width: 250px;">
        </div>
        <button type="submit" class="btn btn-secondary">Filtrar Mês</button>
    </form>
</div>

<div class="layout-split" style="grid-template-columns: 1fr 2fr;">
    <!-- Form de Lançamento Rápido -->
    <div class="card-box">
        <div class="card-title-box">
            <h3>Lançar Novo Custo</h3>
        </div>
        
        <form action="/custos-industriais/novo" method="POST">
            <input type="hidden" name="mes_referencia" value="<?= htmlspecialchars($mes) ?>">
            
            <div class="form-group">
                <label for="descricao" class="form-label">Descrição da Despesa *</label>
                <input type="text" id="descricao" name="descricao" class="form-control" placeholder="Ex: Energia Elétrica Galpão" required>
            </div>

            <div class="form-group">
                <label for="valor" class="form-label">Valor (R$) *</label>
                <input type="number" id="valor" name="valor" class="form-control" step="0.01" min="0.01" placeholder="0,00" required>
            </div>

            <div class="form-group">
                <label for="tipo" class="form-label">Tipo de Despesa</label>
                <select id="tipo" name="tipo" class="form-control">
                    <option value="fixo">Fixo (Aluguel, Pro-labore, etc.)</option>
                    <option value="indireto">Indireto (Energia, Água, Manutenção)</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Salvar Lançamento</button>
        </form>
    </div>

    <!-- Tabela de Lançamentos do Mês -->
    <div class="card-box">
        <div class="card-title-box">
            <h3>Custos Industriais Lancados no Mês (<?= date('m/Y', strtotime($mes . '-01')) ?>)</h3>
            <div style="font-size: 16px; font-weight:700; color:var(--primary);">Total: R$ <?= number_format($totalCustos, 2, ',', '.') ?></div>
        </div>

        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Despesa</th>
                        <th>Tipo</th>
                        <th>Valor</th>
                        <th style="text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($custos)): ?>
                        <tr>
                            <td colspan="4" class="empty-state">
                                Nenhum custo industrial lançado para este mês.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($custos as $c): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($c['descricao']) ?></strong></td>
                                <td>
                                    <?php if ($c['tipo'] === 'fixo'): ?>
                                        <span class="badge badge-secondary">Fixo</span>
                                    <?php else: ?>
                                        <span class="badge badge-info">Indireto</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight: 600;">R$ <?= number_format($c['valor'], 2, ',', '.') ?></td>
                                <td style="text-align: right;">
                                    <a href="/custos-industriais/excluir?id=<?= $c['id'] ?>&mes=<?= $mes ?>" class="btn btn-danger" style="padding: 4px 8px; font-size: 11px;" onclick="return confirm('Deseja excluir este custo?');">Excluir</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
