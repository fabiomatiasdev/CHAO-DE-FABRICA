<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="card-box" style="max-width: 800px; margin: 0 auto;">
    <div class="card-title-box">
        <h3>Preencha os Dados do Retorno</h3>
    </div>

    <form action="<?= $action ?>" method="POST">
        <div class="form-group">
            <label for="ordem_producao_id" class="form-label">Selecione a Ordem de Produção (OP) *</label>
            <select id="ordem_producao_id" name="ordem_producao_id" class="form-control" required>
                <option value="">-- Selecione a OP Ativa em Oficina --</option>
                <?php foreach ($ops as $op): ?>
                    <option value="<?= $op['id'] ?>" data-qtd="<?= $op['quantidade'] ?>">
                        OP #<?= $op['id'] ?> — <?= htmlspecialchars($op['referencia']) ?> (Oficina: <?= htmlspecialchars($op['oficina_nome']) ?> — Custo Costura: R$ <?= number_format($op['mao_obra_peca'], 2, ',', '.') ?>/pç — Meta: <?= $op['quantidade'] ?> pçs)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="quantidade_enviada" class="form-label">Quantidade Enviada Inicialmente *</label>
                <input type="number" id="quantidade_enviada" name="quantidade_enviada" class="form-control" min="1" placeholder="Ex: 200" required>
            </div>

            <div class="form-group">
                <label for="quantidade_retornada_boa" class="form-label">Quantidade Retornada BOA (Sem Defeitos) *</label>
                <input type="number" id="quantidade_retornada_boa" name="quantidade_retornada_boa" class="form-control" min="0" placeholder="Ex: 195" required>
                <span style="font-size: 11px; color: var(--muted);">Será adicionada ao estoque de Produto Acabado.</span>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="quantidade_defeito_perda" class="form-label">Quantidade com Defeito / Perda *</label>
                <input type="number" id="quantidade_defeito_perda" name="quantidade_defeito_perda" class="form-control" min="0" placeholder="Ex: 5" required>
                <span style="font-size: 11px; color: var(--muted);">Será contabilizada no relatório de perdas da produção.</span>
            </div>

            <div class="form-group">
                <label for="data_retorno" class="form-label">Data do Retorno *</label>
                <input type="date" id="data_retorno" name="data_retorno" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
        </div>

        <div class="form-actions">
            <a href="/retornos" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Registrar Retorno</button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectOp = document.getElementById('ordem_producao_id');
        const inputEnviada = document.getElementById('quantidade_enviada');
        const inputBoa = document.getElementById('quantidade_retornada_boa');
        const inputDefeito = document.getElementById('quantidade_defeito_perda');

        selectOp.addEventListener('change', function() {
            const selectedOpt = selectOp.options[selectOp.selectedIndex];
            if (selectedOpt && selectedOpt.value !== "") {
                const targetQtd = selectedOpt.getAttribute('data-qtd');
                
                // Preencher com a meta da OP
                inputEnviada.value = targetQtd;
                inputBoa.value = targetQtd;
                inputDefeito.value = 0;
            } else {
                inputEnviada.value = "";
                inputBoa.value = "";
                inputDefeito.value = "";
            }
        });
    });
</script>

<?php require __DIR__ . '/../layouts/header.php'; ?>
