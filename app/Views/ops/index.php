<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="card-box">
    <div class="card-title-box">
        <h3>Ordens de Produção (OP)</h3>
        <a href="/ops/novo" class="btn btn-primary"><i data-lucide="plus"></i> Nova OP</a>
    </div>

    <!-- Barra de Filtros/Busca -->
    <form action="/ops" method="GET" style="margin-bottom: 20px; display: flex; gap: 10px;">
        <input type="text" name="busca" class="form-control" placeholder="Buscar por Modelo, Referência ou Oficina..." value="<?= htmlspecialchars($busca ?? '') ?>" style="max-width: 400px;">
        <button type="submit" class="btn btn-secondary"><i data-lucide="search"></i> Filtrar</button>
        <?php if (!empty($busca)): ?>
            <a href="/ops" class="btn btn-secondary">Limpar</a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>OP</th>
                    <th>Modelo de Produto</th>
                    <th>Origem / Pedido</th>
                    <th>Oficina / Facção</th>
                    <th class="text-right">Quantidade</th>
                    <th>Prazo Final</th>
                    <th class="text-center">Status</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ops)): ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            <i data-lucide="clipboard-list" style="width:32px;height:32px;"></i>
                            <p>Nenhuma ordem de produção cadastrada.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ops as $op): ?>
                        <tr>
                            <td><strong>OP #<?= $op['id'] ?></strong></td>
                            <td>
                                <strong style="color:var(--primary);"><?= htmlspecialchars($op['referencia']) ?></strong>
                                <div style="font-size:12px; color:var(--muted);"><?= htmlspecialchars($op['modelo_nome']) ?></div>
                                <?php if (!empty($op['variantes'])): ?>
                                    <div style="font-size:11px; margin-top:4px; display:flex; flex-direction:column; gap:2px;">
                                        <?php foreach ($op['variantes'] as $v): ?>
                                            <div>
                                                <span class="badge badge-secondary" style="font-size: 10px; font-weight:normal; padding: 1px 4px;">
                                                    <?= htmlspecialchars($v['cor']) ?> / <?= htmlspecialchars($v['tamanho']) ?>: <strong><?= $v['quantidade'] ?> pçs</strong>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($op['pedido_venda_id']): ?>
                                    <span style="font-weight: 600;">Pedido #<?= $op['pedido_venda_id'] ?></span>
                                    <div style="font-size:11px; color:var(--muted);">Cliente: <?= htmlspecialchars($op['cliente_nome']) ?></div>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Produção Avulsa</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($op['oficina_nome'] ?: 'Não Definida') ?></td>
                            <td class="text-right"><strong><?= number_format($op['quantidade'], 0, ',', '.') ?> pçs</strong></td>
                            <td>
                                <?php 
                                $atrasado = strtotime($op['prazo']) < time() && $op['status'] !== 'concluída' && $op['status'] !== 'cancelada';
                                ?>
                                <span style="font-weight: 600; color: <?= $atrasado ? 'var(--danger)' : '#334155' ?>;">
                                    <?= date('d/m/Y', strtotime($op['prazo'])) ?>
                                    <?php if ($atrasado): ?>
                                        <i data-lucide="alert-triangle" style="width:12px;height:12px;display:inline-block;vertical-align:middle;margin-left:2px;" title="Atrasado!"></i>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if ($op['status'] === 'aberta'): ?>
                                    <span class="badge badge-secondary">Aberta</span>
                                <?php elseif ($op['status'] === 'em andamento'): ?>
                                    <span class="badge badge-info">Em Andamento</span>
                                <?php elseif ($op['status'] === 'concluída'): ?>
                                    <span class="badge badge-success">Concluída</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Cancelada</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                <div class="actions-cell">
                                    <a href="/ops/editar?id=<?= $op['id'] ?>" class="btn btn-secondary btn-sm">
                                        <i data-lucide="edit-2"></i> Editar
                                    </a>
                                    <a href="/ops/excluir?id=<?= $op['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Deseja realmente excluir esta OP?');">
                                        <i data-lucide="trash-2"></i> Excluir
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
