<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="card-box" style="max-width: 800px; margin: 0 auto;">
    <div class="card-title-box">
        <h3>Preencha as Informações da Matéria-Prima</h3>
    </div>

    <form action="<?= $action ?>" method="POST">
        <div class="form-group">
            <label for="nome" class="form-label">Nome do Insumo *</label>
            <input type="text" id="nome" name="nome" class="form-control" value="<?= htmlspecialchars($item['nome'] ?? '') ?>" placeholder="Ex: Tecido Algodão Penteado 30.1" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="unidade_medida" class="form-label">Unidade de Medida *</label>
                <select id="unidade_medida" name="unidade_medida" class="form-control">
                    <option value="M" <?= ($item['unidade_medida'] ?? 'M') === 'M' ? 'selected' : '' ?>>M (Metro)</option>
                    <option value="UN" <?= ($item['unidade_medida'] ?? '') === 'UN' ? 'selected' : '' ?>>UN (Unidade)</option>
                    <option value="KG" <?= ($item['unidade_medida'] ?? '') === 'KG' ? 'selected' : '' ?>>KG (Quilograma)</option>
                    <option value="RL" <?= ($item['unidade_medida'] ?? '') === 'RL' ? 'selected' : '' ?>>RL (Rolo)</option>
                    <option value="CX" <?= ($item['unidade_medida'] ?? '') === 'CX' ? 'selected' : '' ?>>CX (Caixa)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="custo_unitario" class="form-label">Custo Unitário (R$) *</label>
                <input type="number" id="custo_unitario" name="custo_unitario" class="form-control" step="0.0001" value="<?= isset($item) ? (float)$item['custo_unitario'] : '' ?>" placeholder="Ex: 15.50" required>
            </div>
        </div>

        <div class="form-group">
            <label for="fornecedor" class="form-label">Fornecedor</label>
            <input type="text" id="fornecedor" name="fornecedor" class="form-control" value="<?= htmlspecialchars($item['fornecedor'] ?? '') ?>" placeholder="Ex: Têxtil Catarinense S.A.">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="estoque_atual" class="form-label">Estoque Inicial</label>
                <?php if ($item === null): ?>
                    <input type="number" id="estoque_atual" name="estoque_atual" class="form-control" step="0.01" value="" placeholder="0.00">
                    <span style="font-size: 11px; color: var(--muted);">Estoque inserido no cadastro inicial da matéria-prima.</span>
                <?php else: ?>
                    <input type="number" id="estoque_atual" class="form-control" value="<?= (float)$item['estoque_atual'] ?>" readonly disabled style="background-color: #f1f5f9;">
                    <span style="font-size: 11px; color: var(--muted);">Para reajustar o estoque atual, utilize o módulo <a href="/estoque/ajuste">Ajuste de Estoque</a>.</span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="estoque_minimo" class="form-label">Estoque de Alerta (Mínimo) *</label>
                <input type="number" id="estoque_minimo" name="estoque_minimo" class="form-control" step="0.01" value="<?= isset($item) ? (float)$item['estoque_minimo'] : '' ?>" placeholder="0.00" required>
            </div>
        </div>

        <div class="form-actions">
            <a href="/materias" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar Matéria-Prima</button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../layouts/header.php'; ?>
