<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="card-box">
    <div class="card-title-box">
        <h3>Contas a Pagar por Oficina (Apuração de Peças Retornadas)</h3>
    </div>

    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Lançamento</th>
                    <th>Oficina / Facção</th>
                    <th>OP / Modelo</th>
                    <th>Valor Devido</th>
                    <th>Valor Pago</th>
                    <th>Saldo Restante</th>
                    <th>Status</th>
                    <th style="text-align: right; width: 220px;">Registrar Pagamento</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($faturamentos)): ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            <i data-lucide="wallet" style="width:32px;height:32px;"></i>
                            <p>Nenhum lançamento financeiro de oficina encontrado.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($faturamentos as $f): ?>
                        <?php 
                        $saldo = $f['valor_devido'] - $f['valor_pago'];
                        ?>
                        <tr>
                            <td>#<?= $f['id'] ?></td>
                            <td><strong><?= htmlspecialchars($f['oficina_nome']) ?></strong></td>
                            <td>
                                <?php if ($f['op_id']): ?>
                                    <span style="font-weight: 600;">OP #<?= $f['op_id'] ?></span>
                                    <div style="font-size: 12px; color: var(--muted);"><?= htmlspecialchars($f['modelo_nome']) ?></div>
                                <?php else: ?>
                                    <span style="color: var(--muted);">Lançamento Avulso</span>
                                <?php endif; ?>
                            </td>
                            <td>R$ <?= number_format($f['valor_devido'], 2, ',', '.') ?></td>
                            <td style="color: var(--success); font-weight: 600;">R$ <?= number_format($f['valor_pago'], 2, ',', '.') ?></td>
                            <td style="color: <?= $saldo > 0 ? 'var(--danger)' : 'var(--success)' ?>; font-weight: 700;">
                                R$ <?= number_format($saldo, 2, ',', '.') ?>
                            </td>
                            <td>
                                <?php if ($f['status'] === 'pago'): ?>
                                    <span class="badge badge-success">Pago</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Pendente</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <?php if ($saldo > 0): ?>
                                    <form action="/financeiro-faccoes/pagar" method="POST" style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                        <input type="number" name="valor_pago" class="form-control" style="max-width: 110px; padding: 5px 8px; font-size:13px;" step="0.01" max="<?= $saldo ?>" min="0.01" value="<?= $saldo ?>" required>
                                        <button type="submit" class="btn btn-success" style="padding: 6px 10px; font-size:12px;"><i data-lucide="check" style="width:14px;height:14px;"></i> Pagar</button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size: 12px; color: var(--muted); font-weight:600;"><i data-lucide="check-circle" style="width:14px;height:14px;color:var(--success);display:inline-block;vertical-align:middle;margin-right:4px;"></i>Totalmente Pago</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
