<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="card" style="margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
        <div>
            <h3 style="margin: 0 0 6px 0; font-size: 18px; color: #1e293b;">Gestão de Facções (Envios e Retornos)</h3>
            <p style="margin: 0; color: #64748b; font-size: 14px;">Controle o envio fracionado de lotes para oficinas terceirizadas, recebimento de peças boas e acompanhamento de perdas.</p>
        </div>
        <div style="display:flex; gap:12px;">
            <a href="/retornos/envio" class="btn btn-secondary">
                <i data-lucide="send" style="width: 16px; height: 16px; margin-right: 6px;"></i> Novo Envio para Facção
            </a>
            <a href="/retornos/novo" class="btn btn-primary">
                <i data-lucide="undo-2" style="width: 16px; height: 16px; margin-right: 6px;"></i> Novo Retorno de Facção
            </a>
        </div>
    </div>
</div>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success" style="background-color: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
        <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger" style="background-color: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
        <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<!-- Tabela 1: Envios para Facção -->
<div class="card" style="margin-bottom: 24px;">
    <div style="padding: 16px 20px; border-bottom: 1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
        <h4 style="margin: 0; font-size: 15px; color: #1e293b;"><i data-lucide="truck" style="width:16px;height:16px;margin-right:6px;vertical-align:middle;"></i> Lotes Enviados para Facções</h4>
        <span class="badge badge-info"><?= count($envios) ?> Registros</span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID Envio</th>
                    <th>OP Vinculada</th>
                    <th>Oficina / Facção</th>
                    <th>Qtd. Enviada</th>
                    <th>Serviço Solicitado</th>
                    <th>Data Envio</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($envios)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #64748b; padding: 20px;">Nenhum envio registrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($envios as $env): ?>
                        <tr>
                            <td>#<?= $env['id'] ?></td>
                            <td>
                                <strong>OP #<?= $env['op_id'] ?></strong><br>
                                <span style="font-size:12px; color:#64748b;">[<?= htmlspecialchars($env['referencia']) ?>] <?= htmlspecialchars($env['modelo_nome']) ?></span>
                            </td>
                            <td><strong><?= htmlspecialchars($env['oficina_nome']) ?></strong></td>
                            <td><strong><?= $env['quantidade_enviada'] ?> pçs</strong></td>
                            <td><?= htmlspecialchars($env['etapa_destino']) ?></td>
                            <td><?= date('d/m/Y', strtotime($env['data_envio'])) ?></td>
                            <td>
                                <?php if ($env['status'] === 'enviado'): ?>
                                    <span class="badge" style="background:#fef3c7; color:#92400e;">Em Trânsito / Na Oficina</span>
                                <?php elseif ($env['status'] === 'retornado_parcial'): ?>
                                    <span class="badge" style="background:#e0f2fe; color:#0369a1;">Retornado Parcial</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Retornado Total</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Tabela 2: Retornos de Facção -->
<div class="card">
    <div style="padding: 16px 20px; border-bottom: 1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
        <h4 style="margin: 0; font-size: 15px; color: #1e293b;"><i data-lucide="check-circle" style="width:16px;height:16px;margin-right:6px;vertical-align:middle;"></i> Histórico de Retornos Registrados</h4>
        <span class="badge badge-info"><?= count($retornos) ?> Registros</span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID Retorno</th>
                    <th>OP / Modelo</th>
                    <th>Oficina</th>
                    <th>Qtd. Enviada</th>
                    <th>Peças Boas</th>
                    <th>Perdas / Defeito</th>
                    <th>Etapas Concluídas</th>
                    <th>Data Retorno</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($retornos)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: #64748b; padding: 20px;">Nenhum retorno de facção registrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($retornos as $ret): ?>
                        <tr>
                            <td>#<?= $ret['id'] ?></td>
                            <td>
                                <strong>OP #<?= $ret['op_id'] ?></strong><br>
                                <span style="font-size:12px; color:#64748b;">[<?= htmlspecialchars($ret['referencia']) ?>] <?= htmlspecialchars($ret['modelo_nome']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($ret['oficina_nome'] ?? 'Oficina Terceirizada') ?></td>
                            <td><?= $ret['quantidade_enviada'] ?> pçs</td>
                            <td><strong style="color: #166534;"><?= $ret['quantidade_retornada_boa'] ?> pçs</strong></td>
                            <td>
                                <?php if ($ret['quantidade_defeito_perda'] > 0): ?>
                                    <strong style="color: #991b1b;"><?= $ret['quantidade_defeito_perda'] ?> pçs</strong>
                                    <?php if ($ret['motivo_defeito']): ?>
                                        <br><span style="font-size:11px; color:#991b1b;"><?= htmlspecialchars($ret['motivo_defeito']) ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge" style="background:#e2e8f0; color:#334155; font-size:11px;">
                                    <?= htmlspecialchars($ret['etapas_concluidas'] ?? 'Costura') ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($ret['data_retorno'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
