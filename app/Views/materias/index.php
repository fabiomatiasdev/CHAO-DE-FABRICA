<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="card-box">
    <div class="card-title-box">
        <h3>Matérias-Primas & Aviamentos</h3>
        <a href="/materias/novo" class="btn btn-primary"><i data-lucide="plus"></i> Nova Matéria-Prima</a>
    </div>

    <!-- Barra de Filtros/Busca -->
    <form action="/materias" method="GET" style="margin-bottom: 20px; display: flex; gap: 10px;">
        <input type="text" name="busca" class="form-control" placeholder="Buscar por Nome ou Fornecedor..." value="<?= htmlspecialchars($busca ?? '') ?>" style="max-width: 400px;">
        <button type="submit" class="btn btn-secondary"><i data-lucide="search"></i> Filtrar</button>
        <?php if (!empty($busca)): ?>
            <a href="/materias" class="btn btn-secondary">Limpar</a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Nome do Insumo</th>
                    <th>U.M.</th>
                    <th>Custo Unitário</th>
                    <th>Fornecedor</th>
                    <th>Estoque Atual</th>
                    <th>Estoque Mínimo</th>
                    <th>Status Alerta</th>
                    <th style="text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($itens)): ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            <i data-lucide="scissors" style="width:32px;height:32px;"></i>
                            <p>Nenhuma matéria-prima cadastrada.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($itens as $item): ?>
                        <?php 
                        $alertaEstoque = $item['estoque_atual'] <= $item['estoque_minimo'];
                        ?>
                        <tr class="<?= $alertaEstoque ? 'alert-row' : '' ?>">
                            <td>
                                <strong><?= htmlspecialchars($item['nome']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($item['unidade_medida']) ?></td>
                            <td>R$ <?= number_format($item['custo_unitario'], 4, ',', '.') ?></td>
                            <td><?= htmlspecialchars($item['fornecedor'] ?: 'N/D') ?></td>
                            <td style="font-weight: 700; color: <?= $alertaEstoque ? 'var(--danger)' : '#334155' ?>;">
                                <?= number_format($item['estoque_atual'], 2, ',', '.') ?>
                            </td>
                            <td><?= number_format($item['estoque_minimo'], 2, ',', '.') ?></td>
                            <td>
                                <?php if ($alertaEstoque): ?>
                                    <span class="badge badge-danger"><i data-lucide="alert-triangle" style="width:10px;height:10px;margin-right:2px;"></i> Compra Necessária</span>
                                <?php else: ?>
                                    <span class="badge badge-success">OK</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;" class="actions-cell">
                                <a href="/materias/editar?id=<?= $item['id'] ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">
                                    <i data-lucide="edit-2" style="width: 14px; height: 14px;"></i> Editar
                                </a>
                                <a href="/materias/excluir?id=<?= $item['id'] ?>" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;" onclick="return confirm('Deseja realmente excluir esta matéria-prima?');">
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
