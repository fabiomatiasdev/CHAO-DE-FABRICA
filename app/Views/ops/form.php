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
        </div>

        <div style="margin-top: 20px; margin-bottom: 25px; border: 1px solid var(--border); border-radius: 8px; padding: 15px; background-color: #f8fafc;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <h4 style="margin:0; font-size:14px; font-weight:600; color:#334155;">Cores, Tamanhos e Quantidades do Lote (Variações) *</h4>
                <button type="button" class="btn btn-secondary" id="btn-add-variante" style="padding: 5px 10px; font-size:11px;">
                    <i data-lucide="plus" style="width:13px;height:13px;"></i> Adicionar Variação
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table-custom" style="background-color: white;">
                    <thead>
                        <tr>
                            <th>Variação (Cor / Tamanho) *</th>
                            <th style="width: 150px;">Quantidade *</th>
                            <th style="width: 60px; text-align:center;">Ação</th>
                        </tr>
                    </thead>
                    <tbody id="variantes-container">
                        <!-- Template oculto de variação -->
                        <tr id="variante-row-template" style="display:none;">
                            <td>
                                <select class="form-control select-variante-item">
                                    <option value="">-- Selecione a Variação --</option>
                                </select>
                            </td>
                            <td>
                                <input type="number" class="form-control input-variante-qtd" min="1" placeholder="Ex: 50">
                            </td>
                            <td style="text-align:center;">
                                <button type="button" class="btn-remove-variante" style="border:none; background:none; color:var(--danger); cursor:pointer;"><i data-lucide="trash-2" style="width:16px;height:16px;"></i></button>
                            </td>
                        </tr>

                        <!-- Variações salvas (no caso de edição) -->
                        <?php if (!empty($opVariantesSelected)): ?>
                            <?php foreach ($opVariantesSelected as $vSel): ?>
                                <tr class="variante-row-active">
                                    <td>
                                        <select name="variante_ids[]" class="form-control select-variante-item" required>
                                            <option value="<?= $vSel['produto_variante_id'] ?>"><?= htmlspecialchars($vSel['cor']) ?> / <?= htmlspecialchars($vSel['tamanho']) ?></option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="variante_quantidades[]" class="form-control input-variante-qtd" min="1" value="<?= htmlspecialchars($vSel['quantidade']) ?>" required>
                                    </td>
                                    <td style="text-align:center;">
                                        <button type="button" class="btn-remove-variante" style="border:none; background:none; color:var(--danger); cursor:pointer;"><i data-lucide="trash-2" style="width:16px;height:16px;"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="form-row">
            <!-- Oficina Parceira -->
            <div class="form-group">
                <label for="oficina_faccao_id" class="form-label">Oficina Terceirizada (Opcional - Definir agora ou pós-corte)</label>
                <select id="oficina_faccao_id" name="oficina_faccao_id" class="form-control">
                    <option value="">-- Definir Posteriormente / Produção Interna --</option>
                    <?php foreach ($oficinas as $of): ?>
                        <option value="<?= $of['id'] ?>" <?= ($op['oficina_faccao_id'] ?? 0) == $of['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($of['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Local de Armazenamento de Destino (Acabados) -->
            <div class="form-group">
                <label for="local_estoque_id" class="form-label">Local de Estoque (Destino dos Acabados)</label>
                <select id="local_estoque_id" name="local_estoque_id" class="form-control">
                    <option value="">-- Depósito Padrão de Acabados --</option>
                    <?php if (!empty($locaisEstoque)): ?>
                        <?php foreach ($locaisEstoque as $loc): ?>
                            <option value="<?= $loc['id'] ?>" <?= ($op['local_estoque_id'] ?? 0) == $loc['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($loc['nome']) ?> (<?= ucfirst($loc['tipo']) ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <!-- Quantidade Estimada (Calculada) -->
            <div class="form-group">
                <label for="quantidade" class="form-label">Estimativa / Perspectiva de Peças do Lote (Soma)</label>
                <input type="number" id="quantidade" name="quantidade" class="form-control" value="<?= isset($op) ? htmlspecialchars($op['quantidade']) : '0' ?>" readonly style="background-color:#f1f5f9; font-weight:bold; cursor:not-allowed;" required>
                <small class="text-muted" style="font-size: 11px; display:block; margin-top:3px;">A quantidade real produzida será apurada no corte e finalização do processo.</small>
            </div>

            <!-- Operadores -->
            <div class="form-group">
                <label for="operadores" class="form-label">Qtd. de Operadores Alocados *</label>
                <input type="number" id="operadores" name="operadores" class="form-control" value="<?= isset($op) ? htmlspecialchars($op['operadores']) : '1' ?>" placeholder="Ex: 1" min="1" required>
            </div>
        </div>

        <div class="form-row">
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
                <option value="em andamento" <?= ($op['status'] ?? '') === 'em andamento' ? 'selected' : '' ?>>Em Andamento (Baixa no estoque)</option>
                <option value="concluída" <?= ($op['status'] ?? '') === 'concluída' ? 'selected' : '' ?>>Concluída (Peças Recebidas)</option>
                <option value="cancelada" <?= ($op['status'] ?? '') === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
            </select>
            <?php if ($op !== null && $op['estoque_baixado']): ?>
                <span class="text-success" style="font-size: 11px; font-weight:600; display:block; margin-top:5px;"><i data-lucide="check" style="width:12px;height:12px;display:inline-block;vertical-align:middle;margin-right:2px;"></i> As matérias-primas/aviamentos já foram baixados do estoque para esta OP.</span>
            <?php endif; ?>
        </div>

        <!-- Painel de Simulação de Produção e Insumos -->
        <div id="painel-simulacao" style="display: none; margin-top: 25px; margin-bottom: 25px; border: 1px solid var(--border); border-radius: 8px; overflow: hidden;">
            <div style="background-color: #f8fafc; padding: 12px 15px; border-bottom: 1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                <h4 style="margin:0; font-size:13px; font-weight:600; color:#334155;"><i data-lucide="cpu" style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-right:4px;"></i> Simulação de PCP em Tempo Real</h4>
                <span class="badge badge-info" style="font-size:10px; padding:3px 6px;">Cálculo Dinâmico</span>
            </div>
            <div style="padding: 15px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <h5 style="margin-top:0; margin-bottom:12px; color:#475569; font-weight:600; font-size:11px; text-transform:uppercase; border-bottom: 1px dashed var(--border); padding-bottom:5px;">Estimativa de Prazo</h5>
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <div style="display:flex; justify-content:space-between;">
                            <span class="text-muted" style="font-size:13px;">Tempo Padrão Unitário:</span>
                            <span id="sim-tempo-unitario" style="font-weight:600; color:#334155;">-</span>
                        </div>
                        <div style="display:flex; justify-content:space-between;">
                            <span class="text-muted" style="font-size:13px;">Tempo Total do Lote:</span>
                            <span id="sim-tempo-total" style="font-weight:600; color:#334155;">-</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; font-weight:700; border-top:1px dashed var(--border); padding-top:8px;">
                            <span style="font-size:13px; color:var(--primary);">Prazo Efetivo (com operadores):</span>
                            <span id="sim-prazo-horas" style="color:var(--primary);">-</span>
                        </div>
                    </div>
                </div>
                <div>
                    <h5 style="margin-top:0; margin-bottom:12px; color:#475569; font-weight:600; font-size:11px; text-transform:uppercase; border-bottom: 1px dashed var(--border); padding-bottom:5px;">Insumos / Aviamentos do Lote</h5>
                    <div id="sim-insumos-list" style="display:flex; flex-direction:column; gap:8px; max-height: 180px; overflow-y: auto;">
                        <p class="text-muted" style="margin:0; font-size:13px;">Selecione um modelo para ver a necessidade de insumos.</p>
                    </div>
                </div>
            </div>
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
        const inputOperadores = document.getElementById('operadores');
        
        const btnAddVariante = document.getElementById('btn-add-variante');
        const containerVariantes = document.getElementById('variantes-container');
        const templateRow = document.getElementById('variante-row-template');

        // Dados das fichas técnicas e variantes do PHP
        const fichasModelos = <?= json_encode($fichasModelos ?? []) ?>;
        const variantesModelos = <?= json_encode($variantes ?? []) ?>;
        const opVariantesSelected = <?= json_encode($opVariantesSelected ?? []) ?>;

        // Ao mudar o modelo, remove as variações que não pertencem a ele
        selectModelo.addEventListener('change', function() {
            // Limpa as linhas ativas
            const activeRows = containerVariantes.querySelectorAll('.variante-row-active');
            activeRows.forEach(row => row.remove());
            
            // Adiciona uma linha inicial vazia se houver modelo selecionado
            if (this.value) {
                adicionarLinhaVariante();
            }
            recalcularTotal();
        });

        btnAddVariante.addEventListener('click', function() {
            if (!selectModelo.value) {
                alert('Selecione o Modelo de Roupa antes de adicionar variações.');
                return;
            }
            adicionarLinhaVariante();
        });

        // Configura eventos para as linhas carregadas da edição
        const editRows = containerVariantes.querySelectorAll('.variante-row-active');
        editRows.forEach(row => {
            const select = row.querySelector('.select-variante-item');
            const qtyInput = row.querySelector('.input-variante-qtd');
            const btnRemove = row.querySelector('.btn-remove-variante');
            
            // Carregar as opções do modelo atual
            popularSelectVariante(select, selectModelo.value, select.value);

            qtyInput.addEventListener('input', recalcularTotal);
            btnRemove.addEventListener('click', function() {
                row.remove();
                recalcularTotal();
            });
        });

        function adicionarLinhaVariante(selectedVarianteId = null, qtyValue = '') {
            const modeloId = selectModelo.value;
            if (!modeloId) return;

            const clone = templateRow.cloneNode(true);
            clone.removeAttribute('id');
            clone.classList.add('variante-row-active');
            clone.style.display = 'table-row';

            const select = clone.querySelector('.select-variante-item');
            select.name = 'variante_ids[]';
            select.required = true;

            const qtyInput = clone.querySelector('.input-variante-qtd');
            qtyInput.name = 'variante_quantidades[]';
            qtyInput.required = true;
            if (qtyValue) {
                qtyInput.value = qtyValue;
            }

            popularSelectVariante(select, modeloId, selectedVarianteId);

            const btnRemove = clone.querySelector('.btn-remove-variante');
            btnRemove.addEventListener('click', function() {
                clone.remove();
                recalcularTotal();
            });

            qtyInput.addEventListener('input', recalcularTotal);

            containerVariantes.appendChild(clone);
            if (typeof lucide !== 'undefined') lucide.createIcons();
            recalcularTotal();
        }

        function popularSelectVariante(selectElement, modeloId, selectedValue = null) {
            const valorAtual = selectedValue || selectElement.value;
            selectElement.innerHTML = '<option value="">-- Selecione a Variação --</option>';
            
            const filtradas = variantesModelos.filter(v => v.produto_modelo_id == modeloId);
            filtradas.forEach(v => {
                const opt = document.createElement('option');
                opt.value = v.id;
                opt.textContent = `${v.cor} / ${v.tamanho}`;
                if (v.id == valorAtual) {
                    opt.selected = true;
                }
                selectElement.appendChild(opt);
            });
        }

        function recalcularTotal() {
            const inputs = containerVariantes.querySelectorAll('.variante-row-active .input-variante-qtd');
            let total = 0;
            inputs.forEach(input => {
                total += parseInt(input.value) || 0;
            });
            inputQuantidade.value = total;
            atualizarSimulacao();
        }

        if (selectPedido) {
            selectPedido.addEventListener('change', function() {
                const selectedOpt = selectPedido.options[selectPedido.selectedIndex];
                if (selectedOpt && selectedOpt.value !== "") {
                    const modeloId = selectedOpt.getAttribute('data-modelo-id');
                    const qtd = selectedOpt.getAttribute('data-qtd');
                    
                    // Auto-selecionar o modelo
                    selectModelo.value = modeloId;
                    
                    // Limpar variações anteriores
                    const activeRows = containerVariantes.querySelectorAll('.variante-row-active');
                    activeRows.forEach(row => row.remove());
                    
                    // Adicionar uma variação com a quantidade do pedido
                    adicionarLinhaVariante(null, qtd);
                }
            });
        }

        inputOperadores.addEventListener('input', atualizarSimulacao);

        // Rodar simulação no carregamento inicial
        atualizarSimulacao();

        function atualizarSimulacao() {
            const modeloId = selectModelo.value;
            const qtd = parseInt(inputQuantidade.value) || 0;
            const ops = parseInt(inputOperadores.value) || 1;

            const painel = document.getElementById('painel-simulacao');

            if (!modeloId || !fichasModelos[modeloId]) {
                painel.style.display = 'none';
                return;
            }

            painel.style.display = 'block';
            const dadosFicha = fichasModelos[modeloId];
            const tempoPadraoUnitario = parseFloat(dadosFicha.tempo_padrao) || 0;

            // 1. Calcular tempos
            const tempoTotalMinutos = qtd * tempoPadraoUnitario;
            const tempoTotalHoras = tempoTotalMinutos / 60;
            const tempoEfetivoHoras = tempoTotalHoras / ops;

            // Formatação legível dos tempos
            document.getElementById('sim-tempo-unitario').textContent = tempoPadraoUnitario.toFixed(2).replace('.', ',') + ' min';
            
            const totalH = Math.floor(tempoTotalHoras);
            const totalM = Math.round((tempoTotalHoras - totalH) * 60);
            document.getElementById('sim-tempo-total').textContent = `${totalH}h ${totalM}min (${tempoTotalMinutos.toFixed(0)} min)`;

            const efetivoH = Math.floor(tempoEfetivoHoras);
            const efetivoM = Math.round((tempoEfetivoHoras - efetivoH) * 60);
            const diasTrabalho = (tempoEfetivoHoras / 8).toFixed(1); // Jornada média de 8h por dia
            document.getElementById('sim-prazo-horas').textContent = `${efetivoH}h ${efetivoM}min (~ ${diasTrabalho.replace('.', ',')} dias de 8h)`;

            // 2. Calcular aviamentos / insumos do lote
            const containerInsumosList = document.getElementById('sim-insumos-list');
            containerInsumosList.innerHTML = '';

            if (!dadosFicha.insumos || dadosFicha.insumos.length === 0) {
                containerInsumosList.innerHTML = '<p class="text-muted" style="margin:0; font-size:13px;">Nenhum insumo cadastrado na Ficha Técnica.</p>';
            } else {
                dadosFicha.insumos.forEach(ins => {
                    const qtdTotal = ins.quantidade * qtd;
                    const itemDiv = document.createElement('div');
                    itemDiv.style.display = 'flex';
                    itemDiv.style.justify = 'space-between';
                    itemDiv.style.fontSize = '12px';
                    itemDiv.style.padding = '5px 0';
                    itemDiv.style.borderBottom = '1px solid #f1f5f9';
                    
                    const qtdTotalFormatado = Number(qtdTotal.toFixed(2)).toString().replace('.', ',');
                    const qtdUnitFormatado = Number(ins.quantidade.toFixed(4)).toString().replace('.', ',');

                    itemDiv.innerHTML = `
                        <span style="color:#334155;">${ins.nome} <small class="text-muted">(${qtdUnitFormatado} / peça)</small></span>
                        <strong style="color:#475569;">${qtdTotalFormatado} ${ins.unidade_medida}</strong>
                    `;
                    containerInsumosList.appendChild(itemDiv);
                });
            }
        }
    });
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
