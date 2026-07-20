<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="card" style="margin-bottom: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
        <div>
            <h3 style="margin: 0 0 6px 0; font-size: 18px; color: #1e293b;">Locais de Estoque (Armazenadores)</h3>
            <p style="margin: 0; color: #64748b; font-size: 14px;">Cadastre almoxarifados, depósitos e lojas para organizar a entrada e saída de matérias-primas e produtos acabados.</p>
        </div>
        <button onclick="document.getElementById('modalNovoLocal').style.display='flex'" class="btn btn-primary">
            <i data-lucide="plus-circle" style="width: 16px; height: 16px; margin-right: 6px;"></i> Novo Local de Estoque
        </button>
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

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome do Local</th>
                    <th>Tipo</th>
                    <th>Descrição</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($locais)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #64748b; padding: 24px;">Nenhum local de estoque cadastrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($locais as $local): ?>
                        <tr>
                            <td>#<?= $local['id'] ?></td>
                            <td><strong><?= htmlspecialchars($local['nome']) ?></strong></td>
                            <td>
                                <?php if ($local['tipo'] === 'insumos'): ?>
                                    <span class="badge" style="background-color: #e0f2fe; color: #0369a1;">Insumos / Matéria-Prima</span>
                                <?php elseif ($local['tipo'] === 'acabados'): ?>
                                    <span class="badge" style="background-color: #dcfce7; color: #15803d;">Produtos Acabados</span>
                                <?php else: ?>
                                    <span class="badge" style="background-color: #f3e8ff; color: #7e22ce;">Geral / Misto</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($local['descricao'] ?? '-') ?></td>
                            <td>
                                <span class="badge <?= $local['status'] === 'ativo' ? 'badge-success' : 'badge-danger' ?>">
                                    <?= ucfirst($local['status']) ?>
                                </span>
                            </td>
                            <td>
                                <button onclick='editarLocal(<?= json_encode($local) ?>)' class="btn-icon" title="Editar">
                                    <i data-lucide="edit"></i>
                                </button>
                                <?php if ($local['status'] === 'ativo'): ?>
                                    <a href="/estoque/locais/excluir?id=<?= $local['id'] ?>" class="btn-icon text-danger" onclick="return confirm('Deseja inativar este local de estoque?');" title="Inativar">
                                        <i data-lucide="trash-2"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Novo Local -->
<div id="modalNovoLocal" class="modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000;">
    <div class="modal-content card" style="width: 100%; max-width: 500px; padding: 24px; background: #fff; border-radius: 12px;">
        <h3 style="margin-top: 0; margin-bottom: 16px;">Novo Local de Estoque</h3>
        <form action="/estoque/locais/novo" method="POST">
            <div class="form-group" style="margin-bottom: 16px;">
                <label>Nome do Local *</label>
                <input type="text" name="nome" class="form-control" placeholder="Ex: Depósito Central, Loja 1, Almoxarifado" required>
            </div>
            <div class="form-group" style="margin-bottom: 16px;">
                <label>Tipo de Armazenagem *</label>
                <select name="tipo" class="form-control" required>
                    <option value="acabados">Produtos Acabados</option>
                    <option value="insumos">Insumos / Matéria-Prima</option>
                    <option value="geral">Geral / Misto</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
                <label>Descrição / Observações</label>
                <textarea name="descricao" class="form-control" rows="3" placeholder="Informações detalhadas sobre a localização..."></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" onclick="document.getElementById('modalNovoLocal').style.display='none'" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Local</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Local -->
<div id="modalEditarLocal" class="modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000;">
    <div class="modal-content card" style="width: 100%; max-width: 500px; padding: 24px; background: #fff; border-radius: 12px;">
        <h3 style="margin-top: 0; margin-bottom: 16px;">Editar Local de Estoque</h3>
        <form action="/estoque/locais/editar" method="POST">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group" style="margin-bottom: 16px;">
                <label>Nome do Local *</label>
                <input type="text" name="nome" id="edit_nome" class="form-control" required>
            </div>
            <div class="form-group" style="margin-bottom: 16px;">
                <label>Tipo de Armazenagem *</label>
                <select name="tipo" id="edit_tipo" class="form-control" required>
                    <option value="acabados">Produtos Acabados</option>
                    <option value="insumos">Insumos / Matéria-Prima</option>
                    <option value="geral">Geral / Misto</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 16px;">
                <label>Status</label>
                <select name="status" id="edit_status" class="form-control">
                    <option value="ativo">Ativo</option>
                    <option value="inativo">Inativo</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
                <label>Descrição</label>
                <textarea name="descricao" id="edit_descricao" class="form-control" rows="3"></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 12px;">
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
