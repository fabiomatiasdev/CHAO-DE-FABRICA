<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="card-box" style="max-width: 900px; margin: 0 auto;">
    <div class="card-title-box">
        <h3>Definição da Ficha Técnica</h3>
    </div>

    <form action="<?= $action ?>" method="POST">
        <!-- Seleção de Modelo -->
        <div class="form-row">
            <div class="form-group">
                <label for="produto_modelo_id" class="form-label">Modelo de Roupa *</label>
                <?php if ($ficha === null): ?>
                    <select id="produto_modelo_id" name="produto_modelo_id" class="form-control" required>
                        <option value="">-- Selecione o Modelo --</option>
                        <?php foreach ($modelos as $m): ?>
                            <option value="<?= $m['id'] ?>">[<?= htmlspecialchars($m['referencia']) ?>] <?= htmlspecialchars($m['nome']) ?> (<?= htmlspecialchars($m['cor']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="hidden" name="produto_modelo_id" value="<?= $ficha['produto_modelo_id'] ?>">
                    <input type="text" class="form-control" value="[<?= htmlspecialchars($ficha['referencia']) ?>] <?= htmlspecialchars($ficha['modelo_nome']) ?> (<?= htmlspecialchars($ficha['cor']) ?>)" readonly disabled style="background-color: #f1f5f9;">
                <?php endif; ?>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="tempo_padrao" class="form-label">Tempo Padrão de Produção (Minutos) *</label>
                <input type="number" id="tempo_padrao" name="tempo_padrao" class="form-control" value="<?= htmlspecialchars($ficha['tempo_padrao'] ?? '0') ?>" min="0" required>
            </div>

            <div class="form-group">
                <label for="custo_mao_obra" class="form-label">Custo Mão de Obra Direta Estimado (R$) *</label>
                <input type="number" id="custo_mao_obra" name="custo_source" class="form-control" step="0.01" value="<?= htmlspecialchars($ficha['custo_mao_obra'] ?? '0.00') ?>" required>
                <!-- Ajustar campo com nome correto para post -->
                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        const input = document.getElementById("custo_mao_obra");
                        input.name = "custo_mao_obra";
                    });
                </script>
            </div>
        </div>

        <!-- Tabela Dinâmica de Consumo de Insumos -->
        <div style="margin-top: 30px;">
            <div class="card-title-box" style="margin-bottom: 15px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">
                <h4 style="font-size: 15px; font-weight:600; color: #334155;">Consumo de Matérias-Primas (BOM)</h4>
                <button type="button" class="btn btn-secondary" id="btn-add-item" style="padding: 6px 12px; font-size:12px;">
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
                                    <?= htmlspecialchars($mat['nome']) ?> (Custo: R$ <?= number_format($mat['custo_unitario'], 4, ',', '.') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="max-width: 150px;">
                        <label class="form-label">Qtd. Consumida</label>
                        <div style="display:flex; align-items:center; gap:5px;">
                            <input type="number" class="form-control input-quantidade" step="0.0001" min="0.0001" placeholder="0.0000">
                            <span class="badge-um text-muted" style="font-size:12px; font-weight:600; min-width:25px;">U.M</span>
                        </div>
                    </div>
                    
                    <button type="button" class="btn-remove-row btn-remove-item"><i data-lucide="trash-2" style="width:16px;height:16px;"></i></button>
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
                                        <?= htmlspecialchars($mat['nome']) ?> (Custo: R$ <?= number_format($mat['custo_unitario'], 4, ',', '.') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group" style="max-width: 150px;">
                            <label class="form-label">Qtd. Consumida *</label>
                            <div style="display:flex; align-items:center; gap:5px;">
                                <input type="number" name="quantidades[]" class="form-control input-quantidade" step="0.0001" min="0.0001" placeholder="0.0000" required>
                                <span class="badge-um text-muted" style="font-size:12px; font-weight:600; min-width:25px;">-</span>
                            </div>
                        </div>
                        
                        <button type="button" class="btn-remove-row btn-remove-item"><i data-lucide="trash-2" style="width:16px;height:16px;"></i></button>
                    </div>
                <?php else: ?>
                    <?php foreach ($fichaItens as $fItem): ?>
                        <div class="item-row">
                            <div class="form-group">
                                <label class="form-label">Insumo / Matéria-Prima *</label>
                                <select name="materias[]" class="form-control select-materia" required>
                                    <option value="">-- Selecione --</option>
                                    <?php foreach ($materias as $mat): ?>
                                        <option value="<?= $mat['id'] ?>" data-um="<?= htmlspecialchars($mat['unidade_medida']) ?>" <?= $mat['id'] == $fItem['materia_prima_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($mat['nome']) ?> (Custo: R$ <?= number_format($mat['custo_unitario'], 4, ',', '.') ?>)
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
                                    <input type="number" name="quantidades[]" class="form-control input-quantidade" step="0.0001" min="0.0001" value="<?= htmlspecialchars($fItem['quantidade']) ?>" placeholder="0.0000" required>
                                    <span class="badge-um text-muted" style="font-size:12px; font-weight:600; min-width:25px;"><?= htmlspecialchars($um) ?></span>
                                </div>
                            </div>
                            
                            <button type="button" class="btn-remove-row btn-remove-item"><i data-lucide="trash-2" style="width:16px;height:16px;"></i></button>
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
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('consumo-itens-container');
        const template = document.getElementById('row-template');
        const btnAdd = document.getElementById('btn-add-item');

        // Adicionar nova linha
        btnAdd.addEventListener('click', function() {
            const clone = template.cloneNode(true);
            clone.removeAttribute('id');
            clone.style.display = 'flex';
            
            // Atribuir nomes para o array do post
            clone.querySelector('.select-materia').name = 'materias[]';
            clone.querySelector('.select-materia').required = true;
            clone.querySelector('.input-quantidade').name = 'quantidades[]';
            clone.querySelector('.input-quantidade').required = true;
            
            container.appendChild(clone);
            lucide.createIcons();
            
            setupRowEvents(clone);
        });

        // Configurar eventos das linhas existentes
        const rows = container.querySelectorAll('.item-row:not(#row-template)');
        rows.forEach(row => setupRowEvents(row));

        function setupRowEvents(row) {
            const select = row.querySelector('.select-materia');
            const badge = row.querySelector('.badge-um');
            const btnRemove = row.querySelector('.btn-remove-item');

            // Mudar unidade de medida conforme material selecionado
            select.addEventListener('change', function() {
                const selectedOpt = select.options[select.selectedIndex];
                const um = selectedOpt.getAttribute('data-um') || '-';
                badge.textContent = um;
            });

            // Remover linha
            btnRemove.addEventListener('click', function() {
                // Manter pelo menos 1 linha se for a única ativa
                const activeRows = container.querySelectorAll('.item-row:not(#row-template)');
                if (activeRows.length > 1) {
                    row.remove();
                } else {
                    alert('A ficha técnica necessita de ao menos uma matéria-prima.');
                }
            });
        }
    });
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
