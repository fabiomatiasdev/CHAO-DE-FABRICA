<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="card-box">
    <div class="card-title-box">
        <h3>Pedidos de Venda</h3>
        <a href="/pedidos/novo" class="btn btn-primary"><i data-lucide="plus"></i> Novo Pedido</a>
    </div>

    <!-- Barra de Filtros/Busca -->
    <form action="/pedidos" method="GET" style="margin-bottom: 20px; display: flex; gap: 10px;">
        <input type="text" name="busca" class="form-control" placeholder="Buscar por Cliente ou Modelo..." value="<?= htmlspecialchars($busca ?? '') ?>" style="max-width: 400px;">
        <button type="submit" class="btn btn-secondary"><i data-lucide="search"></i> Filtrar</button>
        <?php if (!empty($busca)): ?>
            <a href="/pedidos" class="btn btn-secondary">Limpar</a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Cliente</th>
                    <th>Modelo de Produto</th>
                    <th>Quantidade</th>
                    <th>Tamanho</th>
                    <th>Prazo de Entrega</th>
                    <th>Status</th>
                    <th style="text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pedidos)): ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            <i data-lucide="shopping-cart" style="width:32px;height:32px;"></i>
                            <p>Nenhum pedido de venda cadastrado.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pedidos as $p): ?>
                        <tr>
                            <td><strong>#<?= $p['id'] ?></strong></td>
                            <td><?= htmlspecialchars($p['cliente']) ?></td>
                            <td>
                                <strong style="color:var(--primary);"><?= htmlspecialchars($p['referencia']) ?></strong>
                                <div style="font-size: 12px; color: var(--muted);"><?= htmlspecialchars($p['modelo_nome']) ?></div>
                            </td>
                            <td><?= number_format($p['quantidade'], 0, ',', '.') ?> pçs</td>
                            <td><span style="background-color: #f1f5f9; color: #475569; padding: 2px 6px; border-radius: 4px; font-size: 12px; font-weight:600;"><?= htmlspecialchars($p['tamanho']) ?></span></td>
                            <td>
                                <?php 
                                $atrasado = strtotime($p['prazo_entrega']) < time() && $p['status'] !== 'entregue';
                                ?>
                                <span style="font-weight: 600; color: <?= $atrasado ? 'var(--danger)' : '#334155' ?>;">
                                    <?= date('d/m/Y', strtotime($p['prazo_entrega'])) ?>
                                    <?php if ($atrasado): ?>
                                        <i data-lucide="alert-triangle" style="width:12px;height:12px;display:inline-block;vertical-align:middle;margin-left:2px;" title="Atrasado!"></i>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($p['status'] === 'pendente'): ?>
                                    <span class="badge badge-warning">Pendente</span>
                                <?php elseif ($p['status'] === 'em produção'): ?>
                                    <span class="badge badge-info">Em Produção</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Entregue</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;" class="actions-cell">
                                <a href="/pedidos/editar?id=<?= $p['id'] ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">
                                    <i data-lucide="edit-2" style="width: 14px; height: 14px;"></i> Editar
                                </a>
                                <a href="/pedidos/excluir?id=<?= $p['id'] ?>" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;" onclick="return confirm('Deseja realmente excluir este pedido?');">
                                    <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i> Excluir
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
