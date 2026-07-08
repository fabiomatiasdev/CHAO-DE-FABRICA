<?php require __DIR__ . '/../layouts/header.php'; ?>

<!-- Painel de Rateio -->
<div class="card-box" style="margin-bottom: 25px;">
    <form action="/controle-custos" method="GET" style="display: flex; gap: 15px; align-items: flex-end; margin-bottom: 20px;">
        <div class="form-group" style="margin-bottom:0;">
            <label for="mes" class="form-label" style="margin-bottom:4px;">Selecione o Mês da Produção para Rateio</label>
            <input type="month" id="mes" name="mes" class="form-control" value="<?= htmlspecialchars($mes) ?>" style="max-width: 250px;">
        </div>
        <button type="submit" class="btn btn-secondary">Atualizar Mês</button>
    </form>

    <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); margin-bottom: 0;">
        <div class="stat-card stat-info">
            <span class="stat-title">Custos de Fábrica no Mês</span>
            <span class="stat-value">R$ <?= number_format($totalCustos, 2, ',', '.') ?></span>
            <span class="stat-subtext">Aluguel, luz, etc. lançado em Custos Industriais</span>
        </div>
        <div class="stat-card stat-success">
            <span class="stat-title">Produção Acabada no Mês</span>
            <span class="stat-value"><?= number_format($totalProduzidoMes, 0, ',', '.') ?> peças</span>
            <span class="stat-subtext">Total de peças boas retornadas de facção no mês</span>
        </div>
        <div class="stat-card stat-primary">
            <span class="stat-title">Custo Industrial / Peça</span>
            <span class="stat-value">R$ <?= number_format($custoRateadoPeca, 2, ',', '.') ?></span>
            <span class="stat-subtext">Custo de fábrica rateado igualmente por peça produzida</span>
        </div>
    </div>
</div>

<!-- Tabela de Apuração de Margens/Custo por Peça -->
<div class="card-box">
    <div class="card-title-box">
        <h3>Apuração de Custo Unitário Total de Produção</h3>
    </div>
    
    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Modelo</th>
                    <th>Ficha Técnica</th>
                    <th>Custo MP (Ficha)</th>
                    <th>Custo Mão de Obra (Ficha)</th>
                    <th>Custo Ind. Rateado</th>
                    <th>Custo Total Unitário</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($apuracoes)): ?>
                    <tr>
                        <td colspan="6" class="empty-state">
                            Nenhum modelo de produto ativo encontrado.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($apuracoes as $ap): ?>
                        <tr>
                            <td>
                                <strong>[<?= htmlspecialchars($ap['referencia']) ?>]</strong> <?= htmlspecialchars($ap['nome']) ?>
                                <div style="font-size: 11px; color: var(--muted);">Cor: <?= htmlspecialchars($ap['cor']) ?></div>
                            </td>
                            <td>
                                <?php if ($ap['possui_ficha']): ?>
                                    <span class="badge badge-success">Vinculada</span>
                                <?php else: ?>
                                    <span class="badge badge-warning" title="Modelo sem ficha técnica cadastrada - custos zerados.">Sem Ficha</span>
                                <?php endif; ?>
                            </td>
                            <td>R$ <?= number_format($ap['custo_mp'], 2, ',', '.') ?></td>
                            <td>R$ <?= number_format($ap['custo_mo'], 2, ',', '.') ?></td>
                            <td>R$ <?= number_format($ap['custo_rateio'], 2, ',', '.') ?></td>
                            <td style="font-weight: 700; color: var(--primary); font-size:15px;">
                                R$ <?= number_format($ap['custo_total'], 2, ',', '.') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
