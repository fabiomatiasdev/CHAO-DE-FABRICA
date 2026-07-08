<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="card-box" style="max-width: 800px; margin: 0 auto;">
    <div class="card-title-box">
        <h3>Preencha os Dados do Corte Realizado</h3>
    </div>

    <form action="<?= $action ?>" method="POST">
        <div class="form-group">
            <label for="ordem_producao_id" class="form-label">Selecione a Ordem de Produção (OP) *</label>
            <select id="ordem_producao_id" name="ordem_producao_id" class="form-control" required>
                <option value="">-- Selecione a OP Ativa --</option>
                <?php foreach ($ops as $op): ?>
                    <option value="<?= $op['id'] ?>" data-grade="<?= htmlspecialchars($op['grade_tamanhos']) ?>" data-qtd="<?= $op['quantidade'] ?>">
                        OP #<?= $op['id'] ?> — <?= htmlspecialchars($op['referencia']) ?> - <?= htmlspecialchars($op['modelo_nome']) ?> (Meta: <?= $op['quantidade'] ?> pçs)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="tamanho" class="form-label">Tamanho Cortado *</label>
                <input type="text" id="tamanho" name="tamanho" class="form-control" placeholder="Ex: M" required>
                <span id="grade-helper" style="font-size: 11px; color: var(--muted); display: block; margin-top: 4px;">Grade de tamanhos recomendada: selecione uma OP.</span>
            </div>

            <div class="form-group">
                <label for="quantidade_cortada" class="form-label">Quantidade de Peças Cortadas *</label>
                <input type="number" id="quantidade_cortada" name="quantidade_cortada" class="form-control" placeholder="Ex: 200" min="1" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="responsavel" class="form-label">Responsável pelo Enfesto/Corte *</label>
                <input type="text" id="responsavel" name="responsavel" class="form-control" placeholder="Ex: João Cortador" required>
            </div>

            <div class="form-group">
                <label for="data_corte" class="form-label">Data do Corte *</label>
                <input type="date" id="data_corte" name="data_corte" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
        </div>

        <div class="form-actions">
            <a href="/corte" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar Lançamento</button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectOp = document.getElementById('ordem_producao_id');
        const inputQtd = document.getElementById('quantidade_cortada');
        const helper = document.getElementById('grade-helper');

        selectOp.addEventListener('change', function() {
            const selectedOpt = selectOp.options[selectOp.selectedIndex];
            if (selectedOpt && selectedOpt.value !== "") {
                const grade = selectedOpt.getAttribute('data-grade');
                const qtd = selectedOpt.getAttribute('data-qtd');
                
                helper.textContent = "Grade recomendada do modelo: " + grade;
                inputQtd.value = qtd; // Auto-preenche com a meta da OP
            } else {
                helper.textContent = "Grade de tamanhos recomendada: selecione uma OP.";
                inputQtd.value = "";
            }
        });
    });
</script>

<?php require __DIR__ . '/../layouts/header.php'; ?>
