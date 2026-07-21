<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="card-box">
    <div class="card-title-box">
        <div>
            <h3>Gestão de Facções (Envios e Retornos)</h3>
            <p class="text-muted" style="font-size: 13px; margin-top: 4px;">Controle o envio fracionado de lotes para oficinas terceirizadas, recebimento de peças boas e apuração de perdas.</p>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="/retornos/envio" class="btn btn-secondary">
                <i data-lucide="send"></i> Novo Envio para Facção
            </a>
            <a href="/retornos/novo" class="btn btn-primary">
                <i data-lucide="undo-2"></i> Novo Retorno de Facção
            </a>
        </div>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success" style="margin-bottom: 20px;">
            <i data-lucide="check-circle" style="width:16px;height:16px;margin-right:6px;vertical-align:middle;"></i>
            <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger" style="margin-bottom: 20px;">
            <i data-lucide="alert-triangle" style="width:16px;height:16px;margin-right:6px;vertical-align:middle;"></i>
            <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Tabela 1: Lotes Enviados para Facção -->
    <div style="margin-bottom: 30px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <h4 style="font-size:14px; font-weight:600; color:#334155; text-transform:uppercase; letter-spacing:0.5px;">
                <i data-lucide="truck" style="width:15px;height:15px;margin-right:4px;vertical-align:middle;"></i> Lotes Enviados para Oficinas
            </h4>
            <span class="badge badge-info"><?= count($envios) ?> Lotes em trânsito/oficina</span>
        </div>
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th class="w-80">Envio</th>
                        <th>OP / Modelo</th>
                        <th>Oficina / Facção</th>
                        <th class="text-right">Qtd. Enviada</th>
                        <th>Serviço Solicitado</th>
                        <th>Data Envio</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($envios)): ?>
                        <tr>
                            <td colspan="7" class="empty-state">
                                <i data-lucide="truck" style="width:32px;height:32px;margin-bottom:6px;color:var(--muted);"></i>
                                <p>Nenhum envio registrado para oficinas parceiras.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($envios as $env): ?>
                            <tr>
                                <td>#<?= $env['id'] ?></td>
                                <td>
                                    <strong>OP #<?= $env['op_id'] ?></strong><br>
                                    <span style="font-size:12px; color:var(--muted);">[<?= htmlspecialchars($env['referencia']) ?>] <?= htmlspecialchars($env['modelo_nome']) ?></span>
                                </td>
                                <td><strong><?= htmlspecialchars($env['oficina_nome']) ?></strong></td>
                                <td class="text-right"><strong style="color:var(--foreground);"><?= number_format($env['quantidade_enviada'], 0, ',', '.') ?> pçs</strong></td>
                                <td><?= htmlspecialchars($env['etapa_destino']) ?></td>
                                <td><?= date('d/m/Y', strtotime($env['data_envio'])) ?></td>
                                <td class="text-center">
                                    <?php if ($env['status'] === 'enviado'): ?>
                                        <span class="badge badge-warning">Na Oficina</span>
                                    <?php elseif ($env['status'] === 'retornado_parcial'): ?>
                                        <span class="badge badge-info">Retornado Parcial</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Concluído</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php $pagination = $paginationEnvios ?? null; $pageParamName = 'page_envios'; require __DIR__ . '/../layouts/pagination.php'; ?>
    </div>

    <!-- Tabela 2: Histórico de Retornos Registrados -->
    <div>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <h4 style="font-size:14px; font-weight:600; color:#334155; text-transform:uppercase; letter-spacing:0.5px;">
                <i data-lucide="check-circle" style="width:15px;height:15px;margin-right:4px;vertical-align:middle;"></i> Histórico de Retornos de Oficinas
            </h4>
            <span class="badge badge-secondary"><?= count($retornos) ?> Retornos</span>
        </div>
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th class="w-80">Retorno</th>
                        <th>OP / Modelo</th>
                        <th>Oficina</th>
                        <th class="text-right">Enviado</th>
                        <th class="text-right">Peças Boas</th>
                        <th class="text-right">Perdas / Defeitos</th>
                        <th>Etapas Executadas</th>
                        <th>Data Retorno</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($retornos)): ?>
                        <tr>
                            <td colspan="8" class="empty-state">
                                <i data-lucide="undo-2" style="width:32px;height:32px;margin-bottom:6px;color:var(--muted);"></i>
                                <p>Nenhum retorno de facção registrado até o momento.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($retornos as $ret): ?>
                            <tr>
                                <td>#<?= $ret['id'] ?></td>
                                <td>
                                    <strong>OP #<?= $ret['op_id'] ?></strong><br>
                                    <span style="font-size:12px; color:var(--muted);">[<?= htmlspecialchars($ret['referencia']) ?>] <?= htmlspecialchars($ret['modelo_nome']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($ret['oficina_nome'] ?: 'Oficina Terceirizada') ?></td>
                                <td class="text-right"><?= number_format($ret['quantidade_enviada'], 0, ',', '.') ?> pçs</td>
                                <td class="text-right"><strong style="color:var(--success);"><?= number_format($ret['quantidade_retornada_boa'], 0, ',', '.') ?> pçs</strong></td>
                                <td class="text-right">
                                    <?php if ($ret['quantidade_defeito_perda'] > 0): ?>
                                        <strong style="color:var(--danger);"><?= number_format($ret['quantidade_defeito_perda'], 0, ',', '.') ?> pçs</strong>
                                        <?php if ($ret['motivo_defeito']): ?>
                                            <br><span style="font-size:11px; color:var(--danger);"><?= htmlspecialchars($ret['motivo_defeito']) ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-secondary" style="font-size:11px;">
                                        <?= htmlspecialchars($ret['etapas_concluidas'] ?: 'Costura') ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y', strtotime($ret['data_retorno'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php $pagination = $paginationRetornos ?? null; $pageParamName = 'page_retornos'; require __DIR__ . '/../layouts/pagination.php'; ?>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
