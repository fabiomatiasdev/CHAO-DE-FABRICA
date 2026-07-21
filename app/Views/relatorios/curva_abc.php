<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="card-box">
    <div class="card-title-box">
        <h3>Classificação Curva ABC de Insumos</h3>
        <div style="font-size: 16px; font-weight:700; color:var(--primary);">Valor Global em Estoque: R$ <?= number_format($valorTotalGlobal, 2, ',', '.') ?></div>
    </div>

    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Classificação</th>
                    <th>Insumo / Matéria-Prima</th>
                    <th>Estoque Atual</th>
                    <th>Custo Unitário</th>
                    <th>Valor Total em Estoque</th>
                    <th>% Do Estoque</th>
                    <th>% Acumulado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($curva)): ?>
                    <tr>
                        <td colspan="7" class="empty-state">
                            Nenhuma matéria-prima cadastrada.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($curva as $c): ?>
                        <?php 
                        $badgeClass = 'badge-secondary';
                        if ($c['classe'] === 'A') $badgeClass = 'badge-success';
                        elseif ($c['classe'] === 'B') $badgeClass = 'badge-warning';
                        ?>
                        <tr>
                            <td>
                                <span class="badge <?= $badgeClass ?>" style="font-size:13px; padding: 4px 10px; font-weight:700;">Classe <?= $c['classe'] ?></span>
                            </td>
                            <td><strong><?= htmlspecialchars($c['nome']) ?></strong></td>
                            <td><?= number_format($c['estoque_atual'], 2, ',', '.') ?> <?= htmlspecialchars($c['unidade_medida']) ?></td>
                            <td>R$ <?= number_format($c['custo_unitario'], 4, ',', '.') ?></td>
                            <td style="font-weight: 600;">R$ <?= number_format($c['valor_estoque'], 2, ',', '.') ?></td>
                            <td><?= number_format($c['porc_unitario'], 2, ',', '.') ?>%</td>
                            <td><span style="color:var(--muted); font-size:13px; font-weight:600;"><?= number_format($c['porc_acumulada'], 2, ',', '.') ?>%</span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php require __DIR__ . '/../layouts/pagination.php'; ?>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
