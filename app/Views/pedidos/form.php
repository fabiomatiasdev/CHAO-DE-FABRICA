<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="card-box" style="max-width: 800px; margin: 0 auto;">
    <div class="card-title-box">
        <h3>Preencha as Informações do Pedido Comercial</h3>
    </div>

    <form action="<?= $action ?>" method="POST">
        <div class="form-group">
            <label for="cliente" class="form-label">Nome do Cliente / Razão Social *</label>
            <input type="text" id="cliente" name="cliente" class="form-control" value="<?= htmlspecialchars($pedido['cliente'] ?? '') ?>" placeholder="Ex: Lojas Renner S.A." required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="produto_modelo_id" class="form-label">Modelo do Produto *</label>
                <select id="produto_modelo_id" name="produto_modelo_id" class="form-control" required>
                    <option value="">-- Selecione o Modelo --</option>
                    <?php foreach ($modelos as $m): ?>
                        <option value="<?= $m['id'] ?>" data-grade="<?= htmlspecialchars($m['grade_tamanhos']) ?>" <?= ($pedido['produto_modelo_id'] ?? 0) == $m['id'] ? 'selected' : '' ?>>
                            [<?= htmlspecialchars($m['referencia']) ?>] <?= htmlspecialchars($m['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="max-width: 250px;">
                <label for="tamanho" class="form-label">Tamanho Escolhido *</label>
                <input type="text" id="tamanho" name="tamanho" class="form-control" value="<?= htmlspecialchars($pedido['tamanho'] ?? '') ?>" placeholder="Ex: M" required>
                <span id="grade-helper" style="font-size: 11px; color: var(--muted); display: block; margin-top: 4px;">Grade sugerida: selecione um modelo.</span>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="quantidade" class="form-label">Quantidade de Peças *</label>
                <input type="number" id="quantidade" name="quantidade" class="form-control" value="<?= htmlspecialchars($pedido['quantidade'] ?? '0') ?>" min="1" required>
            </div>

            <div class="form-group">
                <label for="prazo_entrega" class="form-label">Prazo de Entrega Comercial *</label>
                <input type="date" id="prazo_entrega" name="prazo_entrega" class="form-control" value="<?= htmlspecialchars($pedido['prazo_entrega'] ?? '') ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="status" class="form-label">Status Comercial</label>
            <select id="status" name="status" class="form-control">
                <option value="pendente" <?= ($pedido['status'] ?? 'pendente') === 'pendente' ? 'selected' : '' ?>>Pendente (Aguardando Produção)</option>
                <option value="em produção" <?= ($pedido['status'] ?? '') === 'em produção' ? 'selected' : '' ?>>Em Produção</option>
                <option value="entregue" <?= ($pedido['status'] ?? '') === 'entregue' ? 'selected' : '' ?>>Entregue ao Cliente</option>
            </select>
        </div>

        <div class="form-actions">
            <a href="/pedidos" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar Pedido</button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectModelo = document.getElementById('produto_modelo_id');
        const inputTamanho = document.getElementById('tamanho');
        const helper = document.getElementById('grade-helper');

        function updateGradeHelper() {
            const selectedOpt = selectModelo.options[selectModelo.selectedIndex];
            if (selectedOpt && selectedOpt.value !== "") {
                const grade = selectedOpt.getAttribute('data-grade');
                helper.textContent = "Grade do modelo: " + grade;
            } else {
                helper.textContent = "Grade sugerida: selecione um modelo.";
            }
        }

        selectModelo.addEventListener('change', updateGradeHelper);
        updateGradeHelper(); // Executar na carga da página se for edição
    });
</script>

<?php require __DIR__ . '/../layouts/header.php'; ?>
