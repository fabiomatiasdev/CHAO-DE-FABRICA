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
                    <th class="text-center" style="width: 130px;">Tamanho</th>
                    <th class="text-right" style="width: 150px;">Quantidade Cortada</th>
                    <th>Responsável</th>
                    <th>Data do Corte</th>
                    <th style="width: 100px; text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cortes)): ?>
                    <tr>
                        <td colspan="8" class="empty-state">
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
                                <?php if (!empty($c['variantes'])): ?>
                                    <div style="font-size:11px; margin-top:4px; display:flex; flex-direction:column; gap:2px;">
                                        <?php foreach ($c['variantes'] as $v): ?>
                                            <div>
                                                <span class="badge badge-secondary" style="font-size: 10px; font-weight:normal; padding: 1px 4px;">
                                                    <?= htmlspecialchars($v['cor']) ?> / <?= htmlspecialchars($v['tamanho']) ?>: <strong><?= $v['quantidade_cortada'] ?> pçs</strong>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><span style="background-color: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-weight:700; font-size:12px;">Variado</span></td>
                            <td class="text-right"><strong><?= number_format($c['quantidade_cortada'], 0, ',', '.') ?> pçs</strong></td>
                            <td><?= htmlspecialchars($c['responsavel']) ?></td>
                            <td><?= date('d/m/Y', strtotime($c['data_corte'])) ?></td>
                            <td class="text-center">
                                <?php 
                                $isAdmin = isset($_SESSION['is_superadmin']) || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
                                $podeExcluir = isset($_SESSION['pode_excluir']) && $_SESSION['pode_excluir'] === 1;
                                if ($isAdmin || $podeExcluir): 
                                ?>
                                    <a href="/corte/excluir?id=<?= $c['id'] ?>" 
                                       class="btn btn-secondary" 
                                       style="display:inline-flex; align-items:center; justify-content:center; padding: 4px 8px; font-size:11px; background-color:#ef4444; color:white; border-radius:4px; text-decoration:none; gap:4px; border:none;" 
                                       onclick="return confirm('Deseja realmente excluir este lançamento de corte? Isso irá atualizar o status no Chão de Fábrica.');">
                                        <i data-lucide="trash-2" style="width:12px; height:12px;"></i> Excluir
                                    </a>
                                <?php else: ?>
                                    <span style="font-size: 11px; color: var(--muted);">Sem permissão</span>
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
