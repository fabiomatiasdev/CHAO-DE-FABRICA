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
    <div class="card-box">
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

        <!-- Quadro de Custos -->
        <div class="table-responsive" style="margin-bottom: 25px;">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Composição dos Custos Fabris</th>
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

        <!-- Seção de Tempos e Perdas (Abaixo do Custo Fabril) -->
        <div style="border-top: 2px solid #e2e8f0; padding-top: 20px; margin-top: 10px;">
            <h4 style="font-size: 15px; font-weight: 700; color: #334155; margin-bottom: 15px;">Análise de Tempos e Perdas do Lote</h4>

            <div class="dashboard-grid" style="grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px;">
                <!-- Card Tempo Produtivo -->
                <div style="border: 1px solid var(--border); padding: 15px; border-radius: 8px; background: #fafbfc;">
                    <span style="font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase; display:block;">Tempo Produtivo Estimado</span>
                    <div style="font-size:18px; font-weight:700; color:#334155; margin-top:4px;">
                        <?php 
                        $tempoTotal = (float) $diagnostico['tempo_total'];
                        $horas = (int) floor($tempoTotal / 60);
                        $minutos = (int) round(fmod($tempoTotal, 60));
                        echo "{$horas}h {$minutos}min";
                        ?>
                    </div>
                    <span style="font-size:11px; color:var(--muted);">Carga horária total acumulada do lote</span>
                </div>

                <!-- Card Prazo Efetivo -->
                <div style="border: 1px solid var(--border); padding: 15px; border-radius: 8px; background: #fafbfc;">
                    <span style="font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase; display:block;">Prazo Efetivo Estimado</span>
                    <div style="font-size:18px; font-weight:700; color:var(--primary); margin-top:4px;">
                        <?php 
                        $tempoEfetivo = (float) ($diagnostico['tempo_efetivo'] ?? ($tempoTotal / ($op['operadores'] ?: 1)));
                        $horasEf = (int) floor($tempoEfetivo / 60);
                        $minutosEf = (int) round(fmod($tempoEfetivo, 60));
                        $diasEf = number_format(($tempoEfetivo / 60) / 8, 1, ',', '.');
                        echo "{$horasEf}h {$minutosEf}min <small style='font-size:11px; font-weight:normal; color:var(--muted);'> (~ {$diasEf} dias de 8h)</small>";
                        ?>
                    </div>
                    <span style="font-size:11px; color:var(--muted);">Com <?= htmlspecialchars($op['operadores'] ?? 1) ?> operador(es) alocado(s) na OP</span>
                </div>

                <!-- Card Perda Financeira -->
                <div style="border: 1px solid var(--border); padding: 15px; border-radius: 8px; background: #fafbfc;">
                    <span style="font-size:11px; font-weight:700; color:var(--muted); text-transform:uppercase; display:block;">Perda Financeira do Lote</span>
                    <div style="font-size:18px; font-weight:700; color:var(--danger); margin-top:4px;">
                        R$ <?= number_format($diagnostico['perda_financeira'], 2, ',', '.') ?>
                    </div>
                    <span style="font-size:11px; color:var(--muted);">Custo desperdiçado devido a defeitos</span>
                </div>
            </div>

            <!-- Informações Operacionais Adicionais -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; font-size: 13px; color: #334155; display: flex; flex-wrap: wrap; justify-content: space-between; gap: 15px;">
                <div>Cor do Lote: <strong><?= htmlspecialchars($op['cor']) ?></strong></div>
                <div>Oficina Responsável: <strong><?= htmlspecialchars($op['oficina_nome'] ?: 'Produção Interna') ?></strong></div>
                <div>Operadores Alocados: <strong><?= htmlspecialchars($op['operadores'] ?? 1) ?></strong></div>
                <div>Data Prazo: <strong><?= date('d/m/Y', strtotime($op['prazo'])) ?></strong></div>
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
