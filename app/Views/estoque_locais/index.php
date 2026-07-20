<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="card-box">
    <div class="card-title-box">
        <div>
            <h3>Locais de Estoque (Armazenadores)</h3>
            <p class="text-muted" style="font-size: 13px; margin-top: 4px;">Cadastre depósitos, almoxarifados e lojas para organizar o saldo de insumos e produtos acabados.</p>
        </div>
        <button onclick="document.getElementById('modalNovoLocal').style.display='flex'" class="btn btn-primary">
            <i data-lucide="plus"></i> Novo Local de Estoque
        </button>
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

    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th class="w-80">ID</th>
                    <th>Nome do Local</th>
                    <th>Tipo de Armazenagem</th>
                    <th>Descrição / Observações</th>
                    <th class="text-center">Status</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($locais)): ?>
                    <tr>
                        <td colspan="6" class="empty-state">
                            <i data-lucide="warehouse" style="width:36px;height:36px;margin-bottom:8px;color:var(--muted);"></i>
                            <p>Neste momento não há locais de estoque cadastrados.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($locais as $local): ?>
                        <tr>
                            <td>#<?= $local['id'] ?></td>
                            <td>
                                <strong style="color:#1e293b;"><?= htmlspecialchars($local['nome']) ?></strong>
                            </td>
                            <td>
                                <?php if ($local['tipo'] === 'insumos'): ?>
                                    <span class="badge badge-info">Insumos / Tecidos</span>
                                <?php elseif ($local['tipo'] === 'acabados'): ?>
                                    <span class="badge badge-success">Produtos Acabados</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Geral / Misto</span>
                                <?php endif; ?>
                            </td>
                            <td class="allow-wrap"><?= htmlspecialchars($local['descricao'] ?: 'N/D') ?></td>
                            <td class="text-center">
                                <span class="badge <?= $local['status'] === 'ativo' ? 'badge-success' : 'badge-danger' ?>">
                                    <?= ucfirst($local['status']) ?>
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="actions-cell">
                                    <button type="button" onclick='editarLocal(<?= json_encode($local) ?>)' class="btn-action" title="Editar Local">
                                        <i data-lucide="edit-2"></i>
                                    </button>
                                    <?php if ($local['status'] === 'ativo'): ?>
                                        <a href="/estoque/locais/excluir?id=<?= $local['id'] ?>" class="btn-action danger" onclick="return confirm('Deseja inativar este local de estoque?');" title="Inativar">
                                            <i data-lucide="trash-2"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Novo Local -->
<div id="modalNovoLocal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(2px); justify-content: center; align-items: center; z-index: 9999;">
    <div class="card-box" style="width: 100%; max-width: 480px; margin: 0; padding: 28px; box-shadow: var(--shadow-lg);">
        <div class="card-title-box" style="margin-bottom: 20px;">
            <h3 style="font-size: 17px;">Novo Local de Estoque</h3>
            <button type="button" onclick="document.getElementById('modalNovoLocal').style.display='none'" style="border:none; background:none; cursor:pointer; color:var(--muted);"><i data-lucide="x"></i></button>
        </div>
        <form action="/estoque/locais/novo" method="POST">
            <div class="form-group">
                <label class="form-label">Nome do Local *</label>
                <input type="text" name="nome" class="form-control" placeholder="Ex: Depósito Central, Loja 1, Almoxarifado" required>
            </div>
            <div class="form-group">
                <label class="form-label">Tipo de Armazenagem *</label>
                <select name="tipo" class="form-control" required>
                    <option value="acabados">Produtos Acabados</option>
                    <option value="insumos">Insumos / Matéria-Prima</option>
                    <option value="geral">Geral / Misto</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Descrição / Observações</label>
                <textarea name="descricao" class="form-control" rows="3" placeholder="Localização física ou detalhes..." style="height: auto;"></textarea>
            </div>
            <div class="form-actions" style="margin-top: 24px; display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" onclick="document.getElementById('modalNovoLocal').style.display='none'" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Local</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Local -->
<div id="modalEditarLocal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(2px); justify-content: center; align-items: center; z-index: 9999;">
    <div class="card-box" style="width: 100%; max-width: 480px; margin: 0; padding: 28px; box-shadow: var(--shadow-lg);">
        <div class="card-title-box" style="margin-bottom: 20px;">
            <h3 style="font-size: 17px;">Editar Local de Estoque</h3>
            <button type="button" onclick="document.getElementById('modalEditarLocal').style.display='none'" style="border:none; background:none; cursor:pointer; color:var(--muted);"><i data-lucide="x"></i></button>
        </div>
        <form action="/estoque/locais/editar" method="POST">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group">
                <label class="form-label">Nome do Local *</label>
                <input type="text" name="nome" id="edit_nome" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Tipo de Armazenagem *</label>
                <select name="tipo" id="edit_tipo" class="form-control" required>
                    <option value="acabados">Produtos Acabados</option>
                    <option value="insumos">Insumos / Matéria-Prima</option>
                    <option value="geral">Geral / Misto</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" id="edit_status" class="form-control">
                    <option value="ativo">Ativo</option>
                    <option value="inativo">Inativo</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Descrição</label>
                <textarea name="descricao" id="edit_descricao" class="form-control" rows="3" style="height: auto;"></textarea>
            </div>
            <div class="form-actions" style="margin-top: 24px; display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" onclick="document.getElementById('modalEditarLocal').style.display='none'" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">Atualizar Local</button>
            </div>
        </form>
    </div>
</div>

<script>
function editarLocal(local) {
    document.getElementById('edit_id').value = local.id;
    document.getElementById('edit_nome').value = local.nome;
    document.getElementById('edit_tipo').value = local.tipo;
    document.getElementById('edit_status').value = local.status;
    document.getElementById('edit_descricao').value = local.descricao || '';
    document.getElementById('modalEditarLocal').style.display = 'flex';
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
