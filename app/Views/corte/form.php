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
                    <option value="<?= $op['id'] ?>" 
                            data-grade="<?= htmlspecialchars($op['grade_tamanhos']) ?>" 
                            data-qtd="<?= $op['quantidade'] ?>"
                            data-variantes='<?= json_encode($op['variantes']) ?>'>
                        OP #<?= $op['id'] ?> — <?= htmlspecialchars($op['referencia']) ?> - <?= htmlspecialchars($op['modelo_nome']) ?> (Meta: <?= $op['quantidade'] ?> pçs)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Seção de Quantidades por Variação da OP -->
        <div id="secao-variantes-corte" style="margin-top: 20px; margin-bottom: 25px; border: 1px solid var(--border); border-radius: 8px; padding: 15px; background-color: #f8fafc; display: none;">
            <h4 style="margin:0 0 15px 0; font-size:14px; font-weight:600; color:#334155;">Variações e Quantidades Cortadas</h4>
            <div class="table-responsive">
                <table class="table-custom" style="background-color: white;">
                    <thead>
                        <tr>
                            <th style="width: 60px; text-align: center;">Cortar?</th>
                            <th>Variação (Cor / Tamanho)</th>
                            <th style="width: 150px;">Meta OP</th>
                            <th style="width: 200px;">Qtd. Cortada *</th>
                        </tr>
                    </thead>
                    <tbody id="variantes-corte-tbody">
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Seção de Consumo Real de Matéria-Prima / Tecido -->
        <div style="margin-top: 20px; margin-bottom: 25px; border: 1px solid var(--border); border-radius: 8px; padding: 15px; background-color: #f8fafc;">
            <h4 style="margin:0 0 15px 0; font-size:14px; font-weight:600; color:#334155;">Consumo Real de Tecido / Matéria-Prima (Opcional)</h4>
            <div class="form-row">
                <div class="form-group" style="flex: 2;">
                    <label for="materia_prima_id" class="form-label">Tecido / Tecido Principal Utilizado</label>
                    <select id="materia_prima_id" name="materia_prima_id" class="form-control">
                        <option value="">-- Selecione o Tecido / Matéria-Prima --</option>
                        <?php if (!empty($materiasPrimas)): ?>
                            <?php foreach ($materiasPrimas as $mp): ?>
                                <option value="<?= $mp['id'] ?>" data-unidade="<?= htmlspecialchars($mp['unidade_medida']) ?>">
                                    <?= htmlspecialchars($mp['nome']) ?> (Estoque: <?= number_format($mp['estoque_atual'], 2, ',', '.') ?> <?= $mp['unidade_medida'] ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="quantidade_real_utilizada" class="form-label">Qtd. Real Gasta</label>
                    <input type="number" step="0.0001" min="0" id="quantidade_real_utilizada" name="quantidade_real_utilizada" class="form-control" placeholder="Ex: 15.5">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label for="unidade_medida" class="form-label">Unidade</label>
                    <input type="text" id="unidade_medida" name="unidade_medida" class="form-control" placeholder="Ex: KG, M">
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group" style="width: 100%;">
                <label for="responsavel" class="form-label">Responsável pelo Enfesto/Corte *</label>
                <input type="text" id="responsavel" name="responsavel" class="form-control" placeholder="Ex: João Cortador" required>
            </div>

            <div class="form-group" style="width: 100%;">
                <label for="data_corte" class="form-label">Data do Corte *</label>
                <input type="date" id="data_corte" name="data_corte" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
        </div>

        <!-- Opção de Finalização do Corte da OP -->
        <div class="form-group" style="margin-top: 15px; margin-bottom: 20px; background: #eff6ff; padding: 12px 16px; border-radius: 8px; border: 1px solid #bfdbfe;">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin: 0; font-weight: 600; color: #1e40af;">
                <input type="checkbox" name="finalizar_corte" value="1" checked style="transform: scale(1.3); cursor: pointer;">
                Finalizar Etapa de Corte deste Lote (Liberar para Facção / Chão de Fábrica mesmo se cortado menos que a estimativa)
            </label>
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
        const secaoVariantes = document.getElementById('secao-variantes-corte');
        const tbodyVariantes = document.getElementById('variantes-corte-tbody');

        const selectMp = document.getElementById('materia_prima_id');
        const inputUnidade = document.getElementById('unidade_medida');

        if (selectMp && inputUnidade) {
            selectMp.addEventListener('change', function() {
                const opt = this.options[this.selectedIndex];
                if (opt && opt.getAttribute('data-unidade')) {
                    inputUnidade.value = opt.getAttribute('data-unidade');
                }
            });
        }

        selectOp.addEventListener('change', function() {

            const selectedOpt = selectOp.options[selectOp.selectedIndex];
            tbodyVariantes.innerHTML = '';
            
            if (selectedOpt && selectedOpt.value !== "") {
                const variantesRaw = selectedOpt.getAttribute('data-variantes');
                const variantes = JSON.parse(variantesRaw || '[]');
                
                if (variantes.length > 0) {
                    secaoVariantes.style.display = 'block';
                    variantes.forEach(v => {
                        const jaCortado = parseInt(v.quantidade_cortada || 0);
                        const meta = parseInt(v.quantidade_op || 0);
                        const restante = meta - jaCortado;
                        
                        let infoMeta = `<strong>${meta} pçs</strong>`;
                        if (jaCortado > 0) {
                            infoMeta += `<br><span style="font-size: 11px; color: #64748b; display: block; margin-top: 4px;">Já cortado: ${jaCortado} pçs<br>Falta: ${restante > 0 ? restante : 0} pçs</span>`;
                        }

                        const defaultQtd = restante > 0 ? restante : 0;
                        const isConcluido = restante <= 0;

                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td style="text-align: center; vertical-align: middle;">
                                <input type="checkbox" class="chk-variante" ${isConcluido ? '' : 'checked'} style="transform: scale(1.2); cursor: pointer;">
                            </td>
                            <td>
                                <strong>${v.cor} / ${v.tamanho}</strong>
                                <input type="hidden" name="variante_ids[]" value="${v.variante_id}" ${isConcluido ? 'disabled' : ''}>
                            </td>
                            <td>${infoMeta}</td>
                            <td>
                                <input type="number" name="quantidades_cortadas[]" class="form-control" min="1" value="${defaultQtd || ''}" placeholder="0" required ${isConcluido ? 'disabled' : ''}>
                            </td>
                        `;
                        
                        if (isConcluido) {
                            tr.style.opacity = '0.6';
                        }
                        
                        const chk = tr.querySelector('.chk-variante');
                        const inputId = tr.querySelector('input[name="variante_ids[]"]');
                        const inputQtd = tr.querySelector('input[name="quantidades_cortadas[]"]');
                        
                        chk.addEventListener('change', function() {
                            if (this.checked) {
                                inputId.disabled = false;
                                inputQtd.disabled = false;
                                tr.style.opacity = '1';
                            } else {
                                inputId.disabled = true;
                                inputQtd.disabled = true;
                                tr.style.opacity = '0.5';
                            }
                        });
                        
                        tbodyVariantes.appendChild(tr);
                    });
                } else {
                    secaoVariantes.style.display = 'none';
                }
            } else {
                secaoVariantes.style.display = 'none';
            }
        });
    });
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
