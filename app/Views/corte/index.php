<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="card-box">
    <div class="card-title-box">
        <h3>Lançamentos de Corte de Tecidos</h3>
        <a href="/corte/novo" class="btn btn-primary"><i data-lucide="plus"></i> Novo Lançamento de Corte</a>
    </div>

    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Lançamento</th>
                    <th>OP Relacionada</th>
                    <th>Modelo de Produto</th>
                    <th>Tamanho Cortado</th>
                    <th>Quantidade Cortada</th>
                    <th>Responsável</th>
                    <th>Data do Corte</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cortes)): ?>
                    <tr>
                        <td colspan="7" class="empty-state">
                            <i data-lucide="slice" style="width:32px;height:32px;"></i>
                            <p>Nenhum lançamento de corte registrado.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($cortes as $c): ?>
                        <tr>
                            <td>#<?= $c['id'] ?></td>
                            <td><strong>OP #<?= $c['op_id'] ?></strong></td>
                            <td>
                                <strong style="color:var(--primary);"><?= htmlspecialchars($c['referencia']) ?></strong>
                                <div style="font-size:12px; color:var(--muted);"><?= htmlspecialchars($c['modelo_nome']) ?></div>
                            </td>
                            <td><span style="background-color: #f1f5f9; color: #475569; padding: 2px 6px; border-radius: 4px; font-weight:600; font-size:12px;"><?= htmlspecialchars($c['tamanho']) ?></span></td>
                            <td><strong><?= number_format($c['quantidade_cortada'], 0, ',', '.') ?> pçs</strong></td>
                            <td><?= htmlspecialchars($c['responsavel']) ?></td>
                            <td><?= date('d/m/Y', strtotime($c['data_corte'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
