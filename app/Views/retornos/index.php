<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="card-box">
    <div class="card-title-box">
        <h3>Retornos de Oficinas Terceirizadas</h3>
        <a href="/retornos/novo" class="btn btn-primary"><i data-lucide="plus"></i> Lançar Retorno de Peças</a>
    </div>

    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Lançamento</th>
                    <th>OP Relacionada</th>
                    <th>Oficina / Facção</th>
                    <th>Modelo de Produto</th>
                    <th>Qtd. Enviada</th>
                    <th>Qtd. Retornada Boa</th>
                    <th>Qtd. Perdas/Defeito</th>
                    <th>Data Retorno</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($retornos)): ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            <i data-lucide="undo-2" style="width:32px;height:32px;"></i>
                            <p>Nenhum retorno de facção registrado.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($retornos as $rf): ?>
                        <tr>
                            <td>#<?= $rf['id'] ?></td>
                            <td><strong>OP #<?= $rf['op_id'] ?></strong></td>
                            <td><strong><?= htmlspecialchars($rf['oficina_nome']) ?></strong></td>
                            <td>
                                <strong style="color:var(--primary);"><?= htmlspecialchars($rf['referencia']) ?></strong>
                                <div style="font-size: 12px; color:var(--muted);"><?= htmlspecialchars($rf['modelo_nome']) ?></div>
                            </td>
                            <td><?= number_format($rf['quantidade_enviada'], 0, ',', '.') ?> pçs</td>
                            <td style="color: var(--success); font-weight: 700;"><?= number_format($rf['quantidade_retornada_boa'], 0, ',', '.') ?> pçs</td>
                            <td style="color: var(--danger); font-weight: 700;"><?= number_format($rf['quantidade_defeito_perda'], 0, ',', '.') ?> pçs</td>
                            <td><?= date('d/m/Y', strtotime($rf['data_retorno'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
