<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="card-box" style="max-width: 900px; margin: 0 auto;">
    <div class="card-title-box">
        <h3>Definição da Ficha Técnica</h3>
    </div>

    <form action="<?= $action ?>" method="POST">
        <?php if ($ficha !== null): ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars($ficha['id']) ?>">
        <?php endif; ?>
        <!-- Seleção de Modelo -->
        <div class="form-row">
            <div class="form-group">
                <label for="produto_modelo_id" class="form-label">Modelo de Roupa *</label>
                <?php if ($ficha === null): ?>
                    <select id="produto_modelo_id" name="produto_modelo_id" class="form-control" required>
                        <option value="">-- Selecione o Modelo --</option>
                        <?php foreach ($modelos as $m): ?>
                            <option value="<?= $m['id'] ?>">[<?= htmlspecialchars($m['referencia']) ?>]
                                <?= htmlspecialchars($m['nome']) ?> (<?= htmlspecialchars($m['cor']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="hidden" name="produto_modelo_id" value="<?= $ficha['produto_modelo_id'] ?>">
                    <input type="text" class="form-control"
                        value="[<?= htmlspecialchars($ficha['referencia']) ?>] <?= htmlspecialchars($ficha['modelo_nome']) ?> (<?= htmlspecialchars($ficha['cor']) ?>)"
                        readonly disabled style="background-color: #f1f5f9;">
                <?php endif; ?>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="tempo_padrao" class="form-label">Tempo Padrão de Produção (Minutos)</label>
                <input type="number" id="tempo_padrao" name="tempo_padrao" class="form-control"
                    value="<?= isset($ficha) ? (float) $ficha['tempo_padrao'] : '0' ?>" min="0" step="0.01"
                    placeholder="0.00" readonly style="background-color: #f1f5f9; cursor: not-allowed;">
                <span class="text-muted" style="font-size:11px;">Calculado automaticamente pelas operações de costura
                    abaixo.</span>
            </div>

            <div class="form-group">
                <label for="custo_mao_obra" class="form-label">Custo Mão de Obra Direta Estimado (R$) *</label>
                <input type="number" id="custo_mao_obra" name="custo_source" class="form-control" step="0.01"
                    value="<?= isset($ficha) && (float) $ficha['custo_mao_obra'] > 0 ? (float) $ficha['custo_mao_obra'] : '' ?>"
                    placeholder="0.00" required>
                <!-- Ajustar campo com nome correto para post -->
                <script>
                    document.addEventListener("DOMContentLoaded", function () {
                        const input = document.getElementById("custo_mao_obra");
                        input.name = "custo_mao_obra";
                    });
                </script>
            </div>
        </div>

        <!-- Cronometragem de Operações de Costura -->
        <div style="margin-top: 30px; margin-bottom: 30px;">
            <div class="card-title-box"
                style="margin-bottom: 15px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
                <h4 style="font-size: 15px; font-weight:600; color: #334155;">Cronometragem - Operações de Costura</h4>
                <button type="button" class="btn btn-secondary" id="btn-add-operacao"
                    style="padding: 6px 12px; font-size:12px;">
                    <i data-lucide="plus" style="width:14px;height:14px;"></i> Adicionar Operação
                </button>
            </div>

            <div class="table-responsive">
                <table class="table-custom" id="tabela-cronometragem">
                    <thead>
                        <tr>
                            <th>Operador(a)</th>
                            <th>Descrição da Operação *</th>
                            <th style="width: 110px;">Tempo 1 (MM:SS) *</th>
                            <th style="width: 110px;">Tempo 2 (MM:SS) *</th>
                            <th style="width: 110px;">Tempo 3 (MM:SS) *</th>
                            <th style="width: 110px;">Média (min)</th>
                            <th>Observações</th>
                            <th style="width: 50px; text-align: center;">Ação</th>
                        </tr>
                    </thead>
                    <tbody id="operacoes-container">
                        <!-- Template oculto de operação -->
                        <tr id="operacao-template" style="display: none;">
                            <td><input type="text" class="form-control op-operador" placeholder="Operador"></td>
                            <td><input type="text" class="form-control op-descricao" placeholder="Ex: Costurar ombros">
                            </td>
                            <td><input type="text" class="form-control op-tempo1" placeholder="00:00" maxlength="5"
                                    style="text-align:center;"></td>
                            <td><input type="text" class="form-control op-tempo2" placeholder="00:00" maxlength="5"
                                    style="text-align:center;"></td>
                            <td><input type="text" class="form-control op-tempo3" placeholder="00:00" maxlength="5"
                                    style="text-align:center;"></td>
                            <td><input type="number" class="form-control op-media" readonly placeholder="0.00"
                                    style="background-color: #f1f5f9; font-weight: bold;"></td>
                            <td><input type="text" class="form-control op-observacoes" placeholder="Obs"></td>
                            <td style="text-align: center;"><button type="button" class="btn-remove-operacao"
                                    style="border:none; background:none; color:var(--danger); cursor:pointer;"><i
                                        data-lucide="trash-2" style="width:16px;height:16px;"></i></button></td>
                        </tr>

                        <!-- Operações Ativas -->
                        <?php
                        function decToMMSS(float $min): string
                        {
                            $m = (int) floor($min);
                            $s = (int) round(($min - $m) * 60);
                            if ($s >= 60) {
                                $m++;
                                $s = 0;
                            }
                            return sprintf('%02d:%02d', $m, $s);
                        }
                        ?>
                        <?php if (!empty($operacoes)): ?>
                            <?php foreach ($operacoes as $opItem): ?>
                                <tr class="operacao-row">
                                    <td><input type="text" name="op_operadores[]" class="form-control op-operador"
                                            value="<?= htmlspecialchars($opItem['operador'] ?? '') ?>" placeholder="Operador">
                                    </td>
                                    <td><input type="text" name="op_descricoes[]" class="form-control op-descricao"
                                            value="<?= htmlspecialchars($opItem['descricao_operacao'] ?? '') ?>"
                                            placeholder="Ex: Costurar ombros" required></td>
                                    <td><input type="text" name="op_tempo1[]" class="form-control op-tempo1"
                                            value="<?= (float) ($opItem['tempo_1'] ?? 0) == 0 ? '' : decToMMSS((float) $opItem['tempo_1']) ?>"
                                            placeholder="00:00" maxlength="5" style="text-align:center;" required></td>
                                    <td><input type="text" name="op_tempo2[]" class="form-control op-tempo2"
                                            value="<?= (float) ($opItem['tempo_2'] ?? 0) == 0 ? '' : decToMMSS((float) $opItem['tempo_2']) ?>"
                                            placeholder="00:00" maxlength="5" style="text-align:center;" required></td>
                                    <td><input type="text" name="op_tempo3[]" class="form-control op-tempo3"
                                            value="<?= (float) ($opItem['tempo_3'] ?? 0) == 0 ? '' : decToMMSS((float) $opItem['tempo_3']) ?>"
                                            placeholder="00:00" maxlength="5" style="text-align:center;" required></td>
                                    <td><input type="number" class="form-control op-media"
                                            value="<?= (float) ($opItem['media'] ?? 0) == 0 ? '' : number_format((float) $opItem['media'], 2, '.', '') ?>"
                                            readonly placeholder="0.00" style="background-color: #f1f5f9; font-weight: bold;">
                                    </td>
                                    <td><input type="text" name="op_observacoes[]" class="form-control op-observacoes"
                                            value="<?= htmlspecialchars($opItem['observacoes'] ?? '') ?>" placeholder="Obs">
                                    </td>
                                    <td style="text-align: center;"><button type="button" class="btn-remove-operacao"
                                            style="border:none; background:none; color:var(--danger); cursor:pointer;"><i
                                                data-lucide="trash-2" style="width:16px;height:16px;"></i></button></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Painel de Tolerâncias e Folgas -->
            <div
                style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px; border: 1px solid var(--border); padding: 15px; border-radius: 8px; background-color: #f8fafc;">
                <div>
                    <h5 style="margin-top: 0; margin-bottom: 10px; color:#475569; font-weight:600;">Folgas / Tolerâncias
                        (%)</h5>
                    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label" style="font-size:11px;">Necessidades</label>
                            <input type="number" id="folga_necessidades" name="folga_necessidades"
                                class="form-control folga-input" step="0.1" min="0" max="100"
                                value="<?= isset($ficha) ? (float) $ficha['folga_necessidades'] : '5.0' ?>">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label" style="font-size:11px;">Fadiga</label>
                            <input type="number" id="folga_fadiga" name="folga_fadiga" class="form-control folga-input"
                                step="0.1" min="0" max="100"
                                value="<?= isset($ficha) ? (float) $ficha['folga_fadiga'] : '5.0' ?>">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label" style="font-size:11px;">Atrasos Costura</label>
                            <input type="number" id="folga_atrasos" name="folga_atrasos"
                                class="form-control folga-input" step="0.1" min="0" max="100"
                                value="<?= isset($ficha) ? (float) $ficha['folga_atrasos'] : '5.0' ?>">
                        </div>
                    </div>
                </div>
                <div style="display:flex; flex-direction:column; justify-content: space-between;">
                    <div
                        style="display:flex; justify-content:space-between; align-items:center; font-size:13px; color:#475569;">
                        <span>Tempo Observado (Básico):</span>
                        <strong id="label-tempo-observado">0,00 min</strong>
                    </div>
                    <div
                        style="display:flex; justify-content:space-between; align-items:center; font-size:13px; color:#475569;">
                        <span>Folga Total:</span>
                        <strong id="label-folga-total">15,0%</strong>
                        <input type="hidden" id="folga_total" name="folga_total"
                            value="<?= htmlspecialchars($ficha['folga_total'] ?? '15.0') ?>">
                    </div>
                    <div
                        style="display:flex; justify-content:space-between; align-items:center; font-size:13px; color:#475569;">
                        <span>Tempo Acrescido:</span>
                        <strong id="label-tempo-acrescido">0,00 min</strong>
                    </div>
                    <div
                        style="display:flex; justify-content:space-between; align-items:center; font-size:14px; font-weight:700; color:var(--primary); border-top:1px dashed var(--border); padding-top:5px; margin-top:5px;">
                        <span>Tempo Padrão Calculado:</span>
                        <strong id="label-tempo-padrao">0,00 min</strong>
                    </div>
                </div>
            </div>
        </div>


        <!-- Tabela Dinâmica de Consumo de Insumos -->
        <div style="margin-top: 30px;">
            <div class="card-title-box"
                style="margin-bottom: 15px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
                <h4 style="font-size: 15px; font-weight:600; color: #334155;">Consumo de Matérias-Primas (BOM)</h4>
                <button type="button" class="btn btn-secondary" id="btn-add-item"
                    style="padding: 6px 12px; font-size:12px;">
                    <i data-lucide="plus" style="width:14px;height:14px;"></i> Adicionar Insumo
                </button>
            </div>

            <div id="consumo-itens-container">
                <!-- Modelo de Linha Vazia (Oculto) -->
                <div class="item-row" id="row-template" style="display: none;">
                    <div class="form-group">
                        <label class="form-label">Insumo / Matéria-Prima</label>
                        <select class="form-control select-materia">
                            <option value="">-- Selecione --</option>
                            <?php foreach ($materias as $mat): ?>
                                <option value="<?= $mat['id'] ?>" data-um="<?= htmlspecialchars($mat['unidade_medida']) ?>">
                                    <?= htmlspecialchars($mat['nome']) ?> (Custo: R$
                                    <?= number_format($mat['custo_unitario'], 4, ',', '.') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="max-width: 150px;">
                        <label class="form-label">Qtd. Consumida</label>
                        <div style="display:flex; align-items:center; gap:5px;">
                            <input type="number" class="form-control input-quantidade" step="0.0001" min="0.0001"
                                placeholder="0.0000">
                            <span class="badge-um text-muted"
                                style="font-size:12px; font-weight:600; min-width:25px;">U.M</span>
                        </div>
                    </div>

                    <button type="button" class="btn-remove-row btn-remove-item"><i data-lucide="trash-2"
                            style="width:16px;height:16px;"></i></button>
                </div>

                <!-- Linhas Ativas -->
                <?php if (empty($fichaItens)): ?>
                    <!-- Mostrar pelo menos uma linha no cadastro inicial -->
                    <div class="item-row">
                        <div class="form-group">
                            <label class="form-label">Insumo / Matéria-Prima *</label>
                            <select name="materias[]" class="form-control select-materia" required>
                                <option value="">-- Selecione --</option>
                                <?php foreach ($materias as $mat): ?>
                                    <option value="<?= $mat['id'] ?>" data-um="<?= htmlspecialchars($mat['unidade_medida']) ?>">
                                        <?= htmlspecialchars($mat['nome']) ?> (Custo: R$
                                        <?= number_format($mat['custo_unitario'], 4, ',', '.') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group" style="max-width: 150px;">
                            <label class="form-label">Qtd. Consumida *</label>
                            <div style="display:flex; align-items:center; gap:5px;">
                                <input type="number" name="quantidades[]" class="form-control input-quantidade"
                                    step="0.0001" min="0.0001" placeholder="0.0000" required>
                                <span class="badge-um text-muted"
                                    style="font-size:12px; font-weight:600; min-width:25px;">-</span>
                            </div>
                        </div>

                        <button type="button" class="btn-remove-row btn-remove-item"><i data-lucide="trash-2"
                                style="width:16px;height:16px;"></i></button>
                    </div>
                <?php else: ?>
                    <?php foreach ($fichaItens as $fItem): ?>
                        <div class="item-row">
                            <div class="form-group">
                                <label class="form-label">Insumo / Matéria-Prima *</label>
                                <select name="materias[]" class="form-control select-materia" required>
                                    <option value="">-- Selecione --</option>
                                    <?php foreach ($materias as $mat): ?>
                                        <option value="<?= $mat['id'] ?>" data-um="<?= htmlspecialchars($mat['unidade_medida']) ?>"
                                            <?= $mat['id'] == $fItem['materia_prima_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($mat['nome']) ?> (Custo: R$
                                            <?= number_format($mat['custo_unitario'], 4, ',', '.') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group" style="max-width: 150px;">
                                <label class="form-label">Qtd. Consumida *</label>
                                <div style="display:flex; align-items:center; gap:5px;">
                                    <?php
                                    // Achar unidade de medida do item atual
                                    $um = 'M';
                                    foreach ($materias as $mat) {
                                        if ($mat['id'] == $fItem['materia_prima_id']) {
                                            $um = $mat['unidade_medida'];
                                        }
                                    }
                                    ?>
                                    <input type="number" name="quantidades[]" class="form-control input-quantidade"
                                        step="0.0001" min="0.0001" value="<?= (float) $fItem['quantidade'] ?>"
                                        placeholder="0.0000" required>
                                    <span class="badge-um text-muted"
                                        style="font-size:12px; font-weight:600; min-width:25px;"><?= htmlspecialchars($um) ?></span>
                                </div>
                            </div>

                            <button type="button" class="btn-remove-row btn-remove-item"><i data-lucide="trash-2"
                                    style="width:16px;height:16px;"></i></button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-actions">
            <a href="/fichas" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar Ficha Técnica</button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- GERENCIAMENTO DE INSUMOS (BOM) ---
        const containerInsumos = document.getElementById('consumo-itens-container');
        const templateInsumo = document.getElementById('row-template');
        const btnAddInsumo = document.getElementById('btn-add-item');

        btnAddInsumo.addEventListener('click', function () {
            const clone = templateInsumo.cloneNode(true);
            clone.removeAttribute('id');
            clone.style.display = 'flex';

            clone.querySelector('.select-materia').name = 'materias[]';
            clone.querySelector('.select-materia').required = true;
            clone.querySelector('.input-quantidade').name = 'quantidades[]';
            clone.querySelector('.input-quantidade').required = true;

            containerInsumos.appendChild(clone);
            lucide.createIcons();
            setupRowEventsInsumo(clone);
        });

        const rowsInsumos = containerInsumos.querySelectorAll('.item-row:not(#row-template)');
        rowsInsumos.forEach(row => setupRowEventsInsumo(row));

        function setupRowEventsInsumo(row) {
            const select = row.querySelector('.select-materia');
            const badge = row.querySelector('.badge-um');
            const btnRemove = row.querySelector('.btn-remove-item');

            select.addEventListener('change', function () {
                const selectedOpt = select.options[select.selectedIndex];
                const um = selectedOpt.getAttribute('data-um') || '-';
                badge.textContent = um;
            });

            btnRemove.addEventListener('click', function () {
                const activeRows = containerInsumos.querySelectorAll('.item-row:not(#row-template)');
                if (activeRows.length > 1) {
                    row.remove();
                } else {
                    alert('A ficha técnica necessita de ao menos uma matéria-prima.');
                }
            });
        }

        // --- GERENCIAMENTO DE CRONOMETRAGEM DE COSTURA ---
        const containerOps = document.getElementById('operacoes-container');
        const templateOp = document.getElementById('operacao-template');
        const btnAddOp = document.getElementById('btn-add-operacao');

        const inputTempoPadrao = document.getElementById('tempo_padrao');
        const inputNecessidades = document.getElementById('folga_necessidades');
        const inputFadiga = document.getElementById('folga_fadiga');
        const inputAtrasos = document.getElementById('folga_atrasos');
        const inputFolgaTotal = document.getElementById('folga_total');

        const labelTempoObservado = document.getElementById('label-tempo-observado');
        const labelFolgaTotal = document.getElementById('label-folga-total');
        const labelTempoAcrescido = document.getElementById('label-tempo-acrescido');
        const labelTempoPadraoCalculado = document.getElementById('label-tempo-padrao');

        // Adicionar nova operação
        btnAddOp.addEventListener('click', function () {
            const clone = templateOp.cloneNode(true);
            clone.removeAttribute('id');
            clone.classList.add('operacao-row');
            clone.style.display = 'table-row';

            clone.querySelector('.op-operador').name = 'op_operadores[]';

            const desc = clone.querySelector('.op-descricao');
            desc.name = 'op_descricoes[]';
            desc.required = true;

            const t1 = clone.querySelector('.op-tempo1');
            t1.name = 'op_tempo1[]';
            t1.required = true;

            const t2 = clone.querySelector('.op-tempo2');
            t2.name = 'op_tempo2[]';
            t2.required = true;

            const t3 = clone.querySelector('.op-tempo3');
            t3.name = 'op_tempo3[]';
            t3.required = true;

            clone.querySelector('.op-observacoes').name = 'op_observacoes[]';

            containerOps.appendChild(clone);
            lucide.createIcons();
            setupRowEventsOp(clone);
            recalcularCronometragem();
        });

        // Eventos para as linhas de operações existentes (edição)
        const rowsOps = containerOps.querySelectorAll('.operacao-row');
        rowsOps.forEach(row => setupRowEventsOp(row));
        recalcularCronometragem();

        // Converte "MM:SS" para minutos decimais
        function parseTime(str) {
            str = (str || '').trim();
            if (str.includes(':')) {
                const parts = str.split(':');
                const m = parseInt(parts[0]) || 0;
                const s = parseInt(parts[1]) || 0;
                return m + s / 60;
            }
            return parseFloat(str) || 0;
        }

        // Aplica máscara automática MM:SS ao digitar
        function applyTimeMask(input) {
            input.addEventListener('input', function () {
                let v = this.value.replace(/[^0-9]/g, '');
                if (v.length > 4) v = v.slice(0, 4);
                if (v.length > 2) v = v.slice(0, 2) + ':' + v.slice(2);
                this.value = v;
            });
        }

        function setupRowEventsOp(row) {
            const inputsTempo = row.querySelectorAll('.op-tempo1, .op-tempo2, .op-tempo3');
            const inputMedia = row.querySelector('.op-media');
            const btnRemove = row.querySelector('.btn-remove-operacao');

            inputsTempo.forEach(input => {
                applyTimeMask(input);
                input.addEventListener('input', function () {
                    const t1 = parseTime(row.querySelector('.op-tempo1').value);
                    const t2 = parseTime(row.querySelector('.op-tempo2').value);
                    const t3 = parseTime(row.querySelector('.op-tempo3').value);

                    const media = (t1 + t2 + t3) / 3;
                    inputMedia.value = media.toFixed(2);
                    recalcularCronometragem();
                });
            });

            btnRemove.addEventListener('click', function () {
                row.remove();
                recalcularCronometragem();
            });
        }

        // Evento nos inputs de folga
        const inputsFolga = [inputNecessidades, inputFadiga, inputAtrasos];
        inputsFolga.forEach(input => {
            input.addEventListener('input', recalcularCronometragem);
        });

        function recalcularCronometragem() {
            const rows = containerOps.querySelectorAll('.operacao-row');

            // Tempo padrão sempre readonly
            inputTempoPadrao.readOnly = true;
            inputTempoPadrao.style.backgroundColor = '#f1f5f9';

            if (rows.length === 0) {
                inputTempoPadrao.value = '0.00';
                labelTempoObservado.textContent = '0,00 min';
                labelTempoAcrescido.textContent = '0,00 min';
                labelTempoPadraoCalculado.textContent = '0,00 min';
                return;
            }

            let tempoObservado = 0;
            rows.forEach(row => {
                const t1 = parseTime(row.querySelector('.op-tempo1').value);
                const t2 = parseTime(row.querySelector('.op-tempo2').value);
                const t3 = parseTime(row.querySelector('.op-tempo3').value);
                const media = (t1 + t2 + t3) / 3;

                row.querySelector('.op-media').value = media.toFixed(2);
                tempoObservado += media;
            });

            const nec = parseFloat(inputNecessidades.value) || 0;
            const fad = parseFloat(inputFadiga.value) || 0;
            const atr = parseFloat(inputAtrasos.value) || 0;
            const folgaTotal = nec + fad + atr;

            inputFolgaTotal.value = folgaTotal.toFixed(1);
            labelFolgaTotal.textContent = folgaTotal.toFixed(1) + '%';

            const tempoAcrescido = tempoObservado * (folgaTotal / 100);
            const tempoPadraoCalculado = tempoObservado + tempoAcrescido;

            labelTempoObservado.textContent = tempoObservado.toFixed(2).replace('.', ',') + ' min';
            labelTempoAcrescido.textContent = tempoAcrescido.toFixed(2).replace('.', ',') + ' min';
            labelTempoPadraoCalculado.textContent = tempoPadraoCalculado.toFixed(2).replace('.', ',') + ' min';

            inputTempoPadrao.value = tempoPadraoCalculado.toFixed(2);
        }
    });
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>