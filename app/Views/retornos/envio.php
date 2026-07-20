<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="card-box" style="max-width: 760px; margin: 0 auto;">
    <div class="card-title-box">
        <h3>Enviar Lote / Corte para Oficina Parceira</h3>
        <a href="/retornos" class="btn btn-secondary"><i data-lucide="arrow-left"></i> Voltar</a>
    </div>

    <form action="<?= $action ?>" method="POST">
        <div class="form-group">
            <label for="ordem_producao_id" class="form-label">Selecione a Ordem de Produção (OP) *</label>
            <select id="ordem_producao_id" name="ordem_producao_id" class="form-control" required>
                <option value="">-- Selecione a OP Cortada --</option>
                <?php foreach ($ops as $op): ?>
                    <option value="<?= $op['id'] ?>" data-cortado="<?= $op['total_cortado'] ?>" data-qtd="<?= $op['quantidade'] ?>">
                        OP #<?= $op['id'] ?> — [<?= htmlspecialchars($op['referencia']) ?>] <?= htmlspecialchars($op['modelo_nome']) ?> (Cortado: <?= $op['total_cortado'] ?> / Meta: <?= $op['quantidade'] ?> pçs)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="oficina_faccao_id" class="form-label">Oficina Terceirizada (Facção) *</label>
            <select id="oficina_faccao_id" name="oficina_faccao_id" class="form-control" required>
                <option value="">-- Selecione a Oficina Parceira --</option>
                <?php foreach ($oficinas as $of): ?>
                    <option value="<?= $of['id'] ?>">
                        <?= htmlspecialchars($of['nome']) ?> (Capacidade: <?= number_format($of['capacidade_produtiva'], 0, ',', '.') ?> pçs/mês)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="quantidade_enviada" class="form-label">Quantidade Enviada *</label>
                <input type="number" min="1" id="quantidade_enviada" name="quantidade_enviada" class="form-control" placeholder="Ex: 50" required>
                <small class="text-muted" style="font-size:11px; display:block; margin-top:4px;">Permite fracionar a OP e enviar o restante para outra oficina.</small>
            </div>

            <div class="form-group">
                <label for="etapa_destino" class="form-label">Serviço / Etapa Solicitada *</label>
                <select id="etapa_destino" name="etapa_destino" class="form-control" required>
                    <option value="Costura">Apenas Costura</option>
                    <option value="Costura e Arremate">Costura e Arremate</option>
                    <option value="Acabamento Completo">Acabamento Completo (Costura + Passar + Embalar)</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="data_envio" class="form-label">Data de Envio *</label>
                <input type="date" id="data_envio" name="data_envio" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="observacoes" class="form-label">Observações / Instruções de Envio</label>
            <textarea id="observacoes" name="observacoes" class="form-control" rows="3" placeholder="Ex: Enviar linhas pretas e etiquetas junto..." style="height:auto;"></textarea>
        </div>

        <div class="form-actions">
            <a href="/retornos" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary"><i data-lucide="send"></i> Confirmar Envio para Facção</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectOp = document.getElementById('ordem_producao_id');
    const inputQtd = document.getElementById('quantidade_enviada');

    selectOp.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        if (opt && opt.getAttribute('data-cortado')) {
            const cortado = parseInt(opt.getAttribute('data-cortado') || '0');
            const estimativa = parseInt(opt.getAttribute('data-qtd') || '0');
            const sugestao = cortado > 0 ? cortado : estimativa;
            if (sugestao > 0) {
                inputQtd.value = sugestao;
            }
        }
    });
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
