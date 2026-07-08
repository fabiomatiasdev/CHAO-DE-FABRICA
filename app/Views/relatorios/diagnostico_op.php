<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="card-box" style="margin-bottom: 25px;">
    <form action="/relatorios/diagnostico-op" method="GET" style="display: flex; gap: 15px; align-items: flex-end;">
        <div class="form-group" style="margin-bottom:0; flex: 1; max-width: 450px;">
            <label for="op_id" class="form-label">Selecione a Ordem de Produção (OP)</label>
            <select id="op_id" name="op_id" class="form-control" required>
                <option value="">-- Selecione para Analisar --</option>
                <?php foreach ($ops as $o): ?>
                    <option value="<?= $o['id'] ?>" <?= $selectedOpId == $o['id'] ? 'selected' : '' ?>>
                        OP #<?= $o['id'] ?> — <?= htmlspecialchars($o['referencia']) ?> - <?= htmlspecialchars($o['modelo_nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i data-lucide="bar-chart-2" style="width:16px;height:16px;"></i> Diagnosticar</button>
    </form>
</div>

<?php if ($diagnostico): ?>
    <?php 
    $op = $diagnostico['op'];
    ?>
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px;">
        
        <!-- Scorecard Geral -->
        <div style="display:flex; flex-direction:column; gap:25px;">
            <div class="card-box" style="margin-bottom:0;">
                <div class="card-title-box">
                    <h3>Diagnóstico da OP #<?= $op['id'] ?> — [<?= htmlspecialchars($op['referencia']) ?>] <?= htmlspecialchars($op['modelo_nome']) ?></h3>
                </div>

                <div class="dashboard-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 20px;">
                    <div class="stat-card stat-primary">
                        <span class="stat-title">Previsto (Meta)</span>
                        <span class="stat-value"><?= number_format($diagnostico['previsto'], 0, ',', '.') ?> pçs</span>
                    </div>
                    <div class="stat-card stat-success">
                        <span class="stat-title">Realizado Bom</span>
                        <span class="stat-value"><?= number_format($diagnostico['realizado_boa'], 0, ',', '.') ?> pçs</span>
                    </div>
                    <div class="stat-card stat-danger">
                        <span class="stat-title">Realizado Perda</span>
                        <span class="stat-value"><?= number_format($diagnostico['realizado_perda'], 0, ',', '.') ?> pçs</span>
                    </div>
                </div>

                <!-- Eficiência Lote -->
                <div style="margin-bottom: 25px; border: 1px solid var(--border); padding: 15px; border-radius: 8px;">
                    <div style="display:flex; justify-content:space-between; font-weight:600; margin-bottom: 8px;">
                        <span>Eficiência do Lote (Realizado / Meta)</span>
                        <span style="color:var(--primary);"><?= $diagnostico['eficiencia'] ?>%</span>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-bar" style="width: <?= min($diagnostico['eficiencia'], 100) ?>%"></div>
                    </div>
                </div>

                <!-- Quadro de Tempos e Custos -->
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Metragem Analisada</th>
                                <th>Custo Unitário</th>
                                <th>Custo Lote Completo (Estimado/Real)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Custo de Matéria-Prima (BOM)</strong></td>
                                <td>R$ <?= number_format($diagnostico['custo_unitario_mp'], 2, ',', '.') ?></td>
                                <td>R$ <?= number_format($diagnostico['custo_mp_total'], 2, ',', '.') ?></td>
                            </tr>
                            <tr>
                                <td><strong>Custo de Mão de Obra (Oficina)</strong></td>
                                <td>R$ <?= number_format($op['mao_obra_peca'] ?? 0.00, 2, ',', '.') ?></td>
                                <td>R$ <?= number_format($diagnostico['custo_mo_total'], 2, ',', '.') ?></td>
                            </tr>
                            <tr style="background-color: #f8fafc; font-weight: 700;">
                                <td colspan="2" style="text-align: right;">Custo Fabril Total Estimado:</td>
                                <td style="color:var(--primary); font-size:15px;">R$ <?= number_format($diagnostico['custo_mp_total'] + $diagnostico['custo_mo_total'], 2, ',', '.') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Detalhes de Tempos e Perdas -->
        <div style="display:flex; flex-direction:column; gap:25px;">
            <div class="card-box" style="margin-bottom:0;">
                <div class="card-title-box">
                    <h3>Tempos e Perdas</h3>
                </div>
                
                <div style="display:flex; flex-direction:column; gap:20px;">
                    <div>
                        <span style="font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase;">Tempo Produtivo Estimado</span>
                        <div style="font-size:18px; font-weight:700; color:#334155; margin-top:4px;">
                            <?php 
                            $horas = floor($diagnostico['tempo_total'] / 60);
                            $minutos = $diagnostico['tempo_total'] % 60;
                            echo "{$horas}h {$minutos}min";
                            ?>
                        </div>
                        <span style="font-size:11px; color:var(--muted);">Baseado no tempo padrão da ficha técnica</span>
                    </div>

                    <div style="border-top:1px solid var(--border); padding-top:15px;">
                        <span style="font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase;">Perda Financeira do Lote</span>
                        <div style="font-size:18px; font-weight:700; color:var(--danger); margin-top:4px;">
                            R$ <?= number_format($diagnostico['perda_financeira'], 2, ',', '.') ?>
                        </div>
                        <span style="font-size:11px; color:var(--muted);">Custo desperdiçado devido a defeitos</span>
                    </div>

                    <div style="border-top:1px solid var(--border); padding-top:15px;">
                        <span style="font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase;">Informações Adicionais</span>
                        <div style="font-size:13px; color:#334155; margin-top:6px; display:flex; flex-direction:column; gap:5px;">
                            <div>Cor do Lote: <strong><?= htmlspecialchars($op['cor']) ?></strong></div>
                            <div>Oficina Responsável: <strong><?= htmlspecialchars($op['oficina_nome'] ?: 'Produção Interna') ?></strong></div>
                            <div>Data Prazo: <strong><?= date('d/m/Y', strtotime($op['prazo'])) ?></strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
<?php elseif ($selectedOpId > 0): ?>
    <div class="card-box empty-state">
        <i data-lucide="shield-alert" style="width:32px;height:32px;"></i>
        <p>A ordem de produção selecionada não pôde ser analisada (verifique se ela possui ficha técnica vinculada).</p>
    </div>
<?php else: ?>
    <div class="card-box empty-state">
        <i data-lucide="bar-chart-2" style="width:32px;height:32px;"></i>
        <p>Selecione uma Ordem de Produção acima para gerar o diagnóstico.</p>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
