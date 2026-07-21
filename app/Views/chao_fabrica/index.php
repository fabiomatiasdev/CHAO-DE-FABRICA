<?php require __DIR__ . '/../layouts/header.php'; ?>

<!-- Painel de Ações / Apontamento Rápido -->
<div class="card-box" style="margin-bottom: 25px;">
    <div class="card-title-box">
        <h3>Apontar Etapa de Produção (Apontamento Chão de Fábrica)</h3>
    </div>
    
    <form action="/chao-fabrica/apontar" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: flex-end;">
            <div class="form-group" style="margin-bottom:0;">
                <label for="ordem_producao_id" class="form-label">Ordem de Produção (OP)</label>
                <select id="ordem_producao_id" name="ordem_producao_id" class="form-control" required>
                    <option value="">-- Selecione a OP --</option>
                    <?php foreach ($ops as $op): ?>
                        <?php if ($op['op_status'] !== 'concluída'): ?>
                            <option value="<?= $op['id'] ?>" data-variantes='<?= json_encode($op['variantes']) ?>'>OP #<?= $op['id'] ?> — <?= htmlspecialchars($op['referencia']) ?> (<?= $op['quantidade'] ?> pçs)</option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label for="etapa" class="form-label">Etapa Produtiva</label>
                <select id="etapa" name="etapa" class="form-control" required>
                    <option value="">-- Selecione a Etapa --</option>
                    <option value="corte">1. Corte</option>
                    <option value="costura">2. Costura</option>
                    <option value="acabamento">3. Acabamento</option>
                    <option value="revisão">4. Revisão (Qualidade)</option>
                    <option value="embalagem">5. Embalagem</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label for="status" class="form-label">Novo Status</label>
                <select id="status" name="status" class="form-control" required>
                    <option value="em andamento">Em Andamento</option>
                    <option value="conclúido">Concluído</option>
                    <option value="pendente">Pendente / Pausado</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label for="responsavel" class="form-label">Responsável / Operador</label>
                <input type="text" id="responsavel" name="responsavel" class="form-control" placeholder="Nome do operador" required>
            </div>
        </div>

        <!-- Checkboxes para seleção das Variações sendo apontadas -->
        <div id="secao-selecao-variantes" style="display: none; border-top: 1px solid var(--border); padding-top: 15px; margin-top: 5px;">
            <label class="form-label" style="font-weight:600; color:#334155; margin-bottom:8px; display:block;">Selecione as variações que estão sofrendo este apontamento:</label>
            <div id="checkboxes-variantes-container" style="display: flex; flex-wrap: wrap; gap: 15px; background: #f8fafc; padding: 12px; border-radius: 6px; border:1px solid #e2e8f0;">
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary" style="align-self: flex-end;"><i data-lucide="play" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"></i> Gravar Apontamento</button>
        </div>
    </form>
</div>

<!-- Grid Visual do Progresso das OPs -->
<div class="card-box">
    <div class="card-title-box">
        <h3>Quadro de Acompanhamento Produtivo</h3>
    </div>

    <?php if (empty($ops)): ?>
        <div class="empty-state">
            <i data-lucide="activity" style="width:32px;height:32px;"></i>
            <p>Nenhuma ordem de produção em andamento.</p>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <?php foreach ($ops as $op): ?>
                <div style="border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; background-color: white;">
                    <!-- Cabeçalho da OP no Quadro -->
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px; border-bottom:1px solid #f1f5f9; padding-bottom:10px;">
                        <div>
                            <span style="font-size:12px; font-weight:700; color:var(--muted);">OPERAÇÃO</span>
                            <h4 style="font-size:16px; font-weight:700; color:var(--foreground); display:inline-block; margin-left: 8px;">
                                OP #<?= $op['id'] ?> — [<?= htmlspecialchars($op['referencia']) ?>] <?= htmlspecialchars($op['modelo_nome']) ?>
                            </h4>
                        </div>
                        <div style="display:flex; gap:15px; align-items:center;">
                            <span>Meta Total: <strong><?= number_format($op['quantidade'], 0, ',', '.') ?> pçs</strong></span>
                            <?php if ($op['op_status'] === 'concluída'): ?>
                                <span class="badge badge-success">Concluída</span>
                            <?php else: ?>
                                <span class="badge badge-info">Em Produção</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Progresso segmentado por variação -->
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <?php foreach ($op['variantes'] as $v): ?>
                            <div style="border: 1px dashed #e2e8f0; padding: 12px; border-radius: 6px; background-color: #fafbfc;">
                                <div style="font-size: 13px; font-weight:700; color: #475569; margin-bottom: 10px; display:flex; justify-content:space-between; align-items:center; flex-wrap: wrap; gap: 8px;">
                                    <span>Variação: <strong style="color:var(--primary);"><?= htmlspecialchars($v['cor']) ?> / <?= htmlspecialchars($v['tamanho']) ?></strong></span>
                                    <div style="font-size: 12px; font-weight:normal; color: #64748b;">
                                        Meta: <strong><?= $v['quantidade_op'] ?></strong> pçs | 
                                        Cortado: <strong style="color:#d97706;"><?= $v['quantidade_cortada'] ?></strong> pçs | 
                                        Pendente: <strong style="color:#ef4444;"><?= $v['quantidade_pendente'] ?></strong> pçs
                                    </div>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px;">
                                    <?php 
                                    $listaEtapas = ['corte', 'costura', 'acabamento', 'revisão', 'embalagem'];
                                    $etapaLabels = [
                                        'corte' => '1. Corte',
                                        'costura' => '2. Costura',
                                        'acabamento' => '3. Acabamento',
                                        'revisão' => '4. Revisão',
                                        'embalagem' => '5. Embalagem'
                                    ];
                                    
                                    foreach ($listaEtapas as $etNome):
                                        $etDado = $v['etapas'][$etNome] ?? ['status' => 'pendente', 'responsavel' => ''];
                                        $etStatus = $etDado['status'];
                                        $etResp = $etDado['responsavel'];
                                        
                                        // Cores do Card
                                        $bgColor = '#f8fafc';
                                        $borderColor = '#e2e8f0';
                                        $textColor = '#64748b';
                                        $badgeClass = 'badge-secondary';
                                        
                                        if ($etStatus === 'em andamento') {
                                            $bgColor = '#eff6ff';
                                            $borderColor = '#bfdbfe';
                                            $textColor = '#1d4ed8';
                                            $badgeClass = 'badge-info';
                                        } elseif ($etStatus === 'conclúido') {
                                            $bgColor = '#f0fdf4';
                                            $borderColor = '#bbf7d0';
                                            $textColor = '#166534';
                                            $badgeClass = 'badge-success';
                                        }
                                    ?>
                                        <div style="background-color: <?= $bgColor ?>; border: 1px solid <?= $borderColor ?>; padding: 10px; border-radius: 6px;">
                                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                                <span style="font-size:12px; font-weight:600; color: <?= $textColor ?>;"><?= $etapaLabels[$etNome] ?></span>
                                                <span class="badge <?= $badgeClass ?>" style="font-size:8px; padding: 1px 4px;"><?= $etStatus ?></span>
                                            </div>
                                            
                                            <div style="font-size:10px; color: var(--muted); min-height: 20px;">
                                                <?php if ($etStatus === 'conclúido'): ?>
                                                    <i data-lucide="check" style="width:10px;height:10px;color:var(--success);display:inline-block;vertical-align:middle;margin-right:1px;"></i>
                                                    <span><?= htmlspecialchars($etResp ?: 'Concluído') ?></span>
                                                <?php elseif ($etStatus === 'em andamento'): ?>
                                                    <span class="pulse-icon" style="display:inline-block; width:5px; height:5px; background-color:var(--primary); border-radius:50%; margin-right:3px;"></span>
                                                    <span><?= htmlspecialchars($etResp ?: 'Ativo') ?></span>
                                                <?php else: ?>
                                                    <span>Pendente</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php require __DIR__ . '/../layouts/pagination.php'; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectOp = document.getElementById('ordem_producao_id');
        const secaoVariantes = document.getElementById('secao-selecao-variantes');
        const containerCheckboxes = document.getElementById('checkboxes-variantes-container');

        selectOp.addEventListener('change', function() {
            const selectedOpt = selectOp.options[selectOp.selectedIndex];
            containerCheckboxes.innerHTML = '';
            
            if (selectedOpt && selectedOpt.value !== "") {
                const variantesRaw = selectedOpt.getAttribute('data-variantes');
                const variantes = JSON.parse(variantesRaw || '[]');
                
                if (variantes.length > 0) {
                    secaoVariantes.style.display = 'block';
                    variantes.forEach(v => {
                        const label = document.createElement('label');
                        label.style.display = 'flex';
                        label.style.alignItems = 'center';
                        label.style.gap = '6px';
                        label.style.fontSize = '13px';
                        label.style.cursor = 'pointer';
                        label.style.fontWeight = '500';
                        label.style.color = '#475569';
                        
                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.name = 'variante_ids[]';
                        checkbox.value = v.variante_id || '';
                        checkbox.checked = true; // Auto-seleciona todas por padrão
                        
                        label.appendChild(checkbox);
                        label.appendChild(document.createTextNode(`${v.cor} / ${v.tamanho}`));
                        containerCheckboxes.appendChild(label);
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
