<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="card-box" style="max-width: 800px; margin: 0 auto;">
    <div class="card-title-box" style="display:flex; justify-content:space-between; align-items:center;">
        <h3>Lançar Retorno de Oficina / Facção</h3>
        <a href="/retornos" class="btn btn-secondary btn-sm">Voltar</a>
    </div>

    <form action="<?= $action ?>" method="POST">
        <!-- Vínculo com o Envio Pendente -->
        <div class="form-group">
            <label for="envio_faccao_id" class="form-label">Selecione o Envio de Facção Pendente *</label>
            <select id="envio_faccao_id" name="envio_faccao_id" class="form-control">
                <option value="">-- Selecione o Lote Enviado para a Facção --</option>
                <?php if (!empty($envios)): ?>
                    <?php foreach ($envios as $env): ?>
                        <option value="<?= $env['id'] ?>" 
                                data-op-id="<?= $env['op_id'] ?>" 
                                data-qtd-enviada="<?= $env['quantidade_enviada'] ?>"
                                data-etapa="<?= htmlspecialchars($env['etapa_destino']) ?>">
                            Envio #<?= $env['id'] ?> — OP #<?= $env['op_id'] ?> (<?= htmlspecialchars($env['referencia']) ?>) | Oficina: <?= htmlspecialchars($env['oficina_nome']) ?> | Qtd Enviada: <?= $env['quantidade_enviada'] ?> pçs
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <input type="hidden" name="ordem_producao_id" id="ordem_producao_id">

        <!-- Caso não tenha envio gravado antecipadamente -->
        <div id="fallback-op-select" class="form-group" style="display: none;">
            <label for="fallback_op_id" class="form-label">Ou selecione a OP diretamente</label>
            <select id="fallback_op_id" class="form-control">
                <option value="">-- Selecione a OP --</option>
                <?php foreach ($opsFallback as $op): ?>
                    <option value="<?= $op['id'] ?>">OP #<?= $op['id'] ?> — [<?= htmlspecialchars($op['referencia']) ?>] <?= htmlspecialchars($op['modelo_nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group" style="flex:1;">
                <label for="quantidade_enviada" class="form-label">Qtd. Enviada no Lote</label>
                <input type="number" id="quantidade_enviada" name="quantidade_enviada" class="form-control" placeholder="0" readonly style="background:#f1f5f9; font-weight:bold;">
            </div>

            <div class="form-group" style="flex:1;">
                <label for="quantidade_retornada_boa" class="form-label">Qtd. Retornada BOA *</label>
                <input type="number" min="0" id="quantidade_retornada_boa" name="quantidade_retornada_boa" class="form-control" placeholder="Ex: 48" required>
            </div>

            <div class="form-group" style="flex:1;">
                <label for="quantidade_defeito_perda" class="form-label">Qtd. Perda / Defeito *</label>
                <input type="number" min="0" id="quantidade_defeito_perda" name="quantidade_defeito_perda" class="form-control" value="0" placeholder="Ex: 2" required>
            </div>
        </div>

        <!-- Motivo / Detalhamento do Defeito -->
        <div class="form-group" style="margin-bottom: 20px;">
            <label for="motivo_defeito" class="form-label">Detalhamento das Perdas / Defeitos (Qualidade)</label>
            <input type="text" id="motivo_defeito" name="motivo_defeito" class="form-control" placeholder="Ex: 2 peças com costura torta / furo no tecido">
        </div>

        <!-- Etapas Concluídas pela Facção -->
        <div style="margin-top: 20px; margin-bottom: 25px; border: 1px solid var(--border); border-radius: 8px; padding: 15px; background-color: #f8fafc;">
            <h4 style="margin:0 0 10px 0; font-size:14px; font-weight:600; color:#334155;">Etapas Realizadas nesta Facção *</h4>
            <p style="margin:0 0 12px 0; font-size:12px; color:#64748b;">Marque quais funções a facção executou. As etapas não marcadas retornarão ao Chão de Fábrica interno para finalização.</p>
            
            <div style="display:flex; gap:20px; flex-wrap:wrap;">
                <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                    <input type="checkbox" name="etapas_concluidas[]" value="costura" checked style="transform:scale(1.2);"> Costura
                </label>
                <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                    <input type="checkbox" name="etapas_concluidas[]" value="acabamento" style="transform:scale(1.2);"> Acabamento / Arremate
                </label>
                <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                    <input type="checkbox" name="etapas_concluidas[]" value="revisão" style="transform:scale(1.2);"> Revisão / Passar
                </label>
                <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                    <input type="checkbox" name="etapas_concluidas[]" value="embalagem" style="transform:scale(1.2);"> Embalagem
                </label>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group" style="flex:1;">
                <label for="data_retorno" class="form-label">Data do Retorno *</label>
                <input type="date" id="data_retorno" name="data_retorno" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
        </div>

        <div class="form-actions">
            <a href="/retornos" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar Retorno e Atualizar Chão de Fábrica</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectEnvio = document.getElementById('envio_faccao_id');
    const inputOpId = document.getElementById('ordem_producao_id');
    const inputQtdEnviada = document.getElementById('quantidade_enviada');
    const fallbackBlock = document.getElementById('fallback-op-select');
    const fallbackOpSelect = document.getElementById('fallback_op_id');

    if (selectEnvio.options.length <= 1) {
        fallbackBlock.style.display = 'block';
    }

    selectEnvio.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        if (opt && opt.value !== "") {
            inputOpId.value = opt.getAttribute('data-op-id') || '';
            inputQtdEnviada.value = opt.getAttribute('data-qtd-enviada') || '0';
        } else {
            inputOpId.value = '';
            inputQtdEnviada.value = '0';
        }
    });

    fallbackOpSelect.addEventListener('change', function() {
        if (!inputOpId.value) {
            inputOpId.value = this.value;
        }
    });
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
