<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="card-box" style="max-width: 800px; margin: 0 auto;">
    <div class="card-title-box">
        <h3>Definição dos Dados da OP</h3>
    </div>

    <form action="<?= $action ?>" method="POST">
        <!-- Vínculo com Pedido de Venda -->
        <div class="form-group">
            <label for="pedido_venda_id" class="form-label">Vincular a Pedido Comercial (Opcional)</label>
            <?php if ($op === null): ?>
                <select id="pedido_venda_id" name="pedido_venda_id" class="form-control">
                    <option value="">-- Produção Avulsa (Sem vínculo comercial) --</option>
                    <?php foreach ($pedidos as $ped): ?>
                        <option value="<?= $ped['id'] ?>" data-modelo-id="<?= $ped['produto_modelo_id'] ?? 0 ?>" data-qtd="<?= $ped['quantidade'] ?>">
                            Pedido #<?= $ped['id'] ?> — <?= htmlspecialchars($ped['cliente']) ?> (<?= $ped['quantidade'] ?> pçs de <?= htmlspecialchars($ped['referencia']) ?> - Tam: <?= htmlspecialchars($ped['tamanho']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <input type="hidden" name="pedido_venda_id" value="<?= $op['pedido_venda_id'] ?>">
                <input type="text" class="form-control" value="<?= $op['pedido_venda_id'] ? "Vinculada ao Pedido #{$op['pedido_venda_id']}" : 'Produção Avulsa' ?>" readonly disabled style="background-color: #f1f5f9;">
            <?php endif; ?>
        </div>

        <div class="form-row">
            <!-- Modelo de Produto -->
            <div class="form-group">
                <label for="produto_modelo_id" class="form-label">Modelo de Roupa *</label>
                <select id="produto_modelo_id" name="produto_modelo_id" class="form-control" required <?= $op !== null ? 'disabled' : '' ?>>
                    <option value="">-- Selecione o Modelo --</option>
                    <?php foreach ($modelos as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= ($op['produto_modelo_id'] ?? 0) == $m['id'] ? 'selected' : '' ?>>
                            [<?= htmlspecialchars($m['referencia']) ?>] <?= htmlspecialchars($m['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($op !== null): ?>
                    <input type="hidden" name="produto_modelo_id" value="<?= $op['produto_modelo_id'] ?>">
                <?php endif; ?>
            </div>

            <!-- Oficina Parceira -->
            <div class="form-group">
                <label for="oficina_faccao_id" class="form-label">Oficina Terceirizada (Costura)</label>
                <select id="oficina_faccao_id" name="oficina_faccao_id" class="form-control">
                    <option value="">-- Produção Interna / Não Definida --</option>
                    <?php foreach ($oficinas as $of): ?>
                        <option value="<?= $of['id'] ?>" <?= ($op['oficina_faccao_id'] ?? 0) == $of['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($of['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <!-- Quantidade -->
            <div class="form-group">
                <label for="quantidade" class="form-label">Quantidade de Peças Lote *</label>
                <input type="number" id="quantidade" name="quantidade" class="form-control" value="<?= htmlspecialchars($op['quantidade'] ?? '0') ?>" min="1" required>
            </div>

            <!-- Prazo -->
            <div class="form-group">
                <label for="prazo" class="form-label">Prazo Final para Entrega *</label>
                <input type="date" id="prazo" name="prazo" class="form-control" value="<?= htmlspecialchars($op['prazo'] ?? '') ?>" required>
            </div>
        </div>

        <!-- Status -->
        <div class="form-group">
            <label for="status" class="form-label">Status da Produção</label>
            <select id="status" name="status" class="form-control">
                <option value="aberta" <?= ($op['status'] ?? 'aberta') === 'aberta' ? 'selected' : '' ?>>Aberta (Aguardando Matéria-Prima)</option>
                <option value="em andamento" <?= ($op['status'] ?? '') === 'em andamento' ? 'selected' : '' ?>>Em Andamento</option>
                <option value="concluída" <?= ($op['status'] ?? '') === 'concluída' ? 'selected' : '' ?>>Concluída (Peças Recebidas)</option>
                <option value="cancelada" <?= ($op['status'] ?? '') === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
            </select>
        </div>

        <div class="form-actions">
            <a href="/ops" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar Ordem de Produção</button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectPedido = document.getElementById('pedido_venda_id');
        const selectModelo = document.getElementById('produto_modelo_id');
        const inputQuantidade = document.getElementById('quantidade');

        if (selectPedido) {
            selectPedido.addEventListener('change', function() {
                const selectedOpt = selectPedido.options[selectPedido.selectedIndex];
                if (selectedOpt && selectedOpt.value !== "") {
                    const modeloId = selectedOpt.getAttribute('data-modelo-id');
                    const qtd = selectedOpt.getAttribute('data-qtd');
                    
                    // Auto-selecionar o modelo
                    selectModelo.value = modeloId;
                    // Auto-preencher quantidade
                    inputQuantidade.value = qtd;
                }
            });
        }
    });
</script>

<?php require __DIR__ . '/../layouts/header.php'; ?>
