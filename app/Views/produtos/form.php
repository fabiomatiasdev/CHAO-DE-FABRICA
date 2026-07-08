<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="card-box" style="max-width: 800px; margin: 0 auto;">
    <div class="card-title-box">
        <h3>Preencha as Especificações do Modelo</h3>
    </div>

    <form action="<?= $action ?>" method="POST" enctype="multipart/form-data">
        <div class="form-row">
            <div class="form-group">
                <label for="nome" class="form-label">Nome do Modelo *</label>
                <input type="text" id="nome" name="nome" class="form-control" value="<?= htmlspecialchars($modelo['nome'] ?? '') ?>" placeholder="Ex: Camiseta Básica Masculina" required>
            </div>
            
            <div class="form-group">
                <label for="referencia" class="form-label">Referência / Código *</label>
                <input type="text" id="referencia" name="referencia" class="form-control" value="<?= htmlspecialchars($modelo['referencia'] ?? '') ?>" placeholder="Ex: CAM-001" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="categoria" class="form-label">Categoria *</label>
                <input type="text" id="categoria" name="categoria" class="form-control" value="<?= htmlspecialchars($modelo['categoria'] ?? '') ?>" placeholder="Ex: Camisetas, Calças, Vestidos" required>
            </div>

            <div class="form-group">
                <label for="cor" class="form-label">Cor Principal *</label>
                <input type="text" id="cor" name="cor" class="form-control" value="<?= htmlspecialchars($modelo['cor'] ?? '') ?>" placeholder="Ex: Azul Royal, Preto, Mescla" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="grade_tamanhos" class="form-label">Grade de Tamanhos (Separado por vírgula) *</label>
                <input type="text" id="grade_tamanhos" name="grade_tamanhos" class="form-control" value="<?= htmlspecialchars($modelo['grade_tamanhos'] ?? 'P,M,G,GG') ?>" placeholder="Ex: P,M,G,GG ou 38,40,42,44" required>
                <span style="font-size: 11px; color: var(--muted);">Define a grade de tamanhos aceita para OPs, cortes e estoque.</span>
            </div>

            <div class="form-group">
                <label for="status" class="form-label">Status do Modelo</label>
                <select id="status" name="status" class="form-control">
                    <option value="ativo" <?= ($modelo['status'] ?? 'ativo') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                    <option value="inativo" <?= ($modelo['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                </select>
            </div>
        </div>

        <div class="form-group" style="margin-top: 15px;">
            <label for="imagem" class="form-label">Imagem do Modelo (Opcional)</label>
            <input type="file" id="imagem" name="imagem" class="form-control" accept="image/*">
            <?php if (!empty($modelo['imagem'])): ?>
                <div style="margin-top: 10px; display: flex; align-items: center; gap: 10px;">
                    <img src="<?= htmlspecialchars($modelo['imagem']) ?>" alt="Atual" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border);">
                    <span style="font-size: 12px; color: var(--muted);">Imagem atual cadastrada. Se selecionar outra, a antiga será substituída.</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <a href="/produtos" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar Modelo</button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../layouts/header.php'; ?>
