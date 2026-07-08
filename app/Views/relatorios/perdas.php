<?php require __DIR__ . '/../layouts/header.php'; ?>

<!-- Painel de Filtros -->
<div class="card-box" style="margin-bottom: 25px;">
    <form action="/relatorios/perdas" method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: flex-end;">
        <div class="form-group" style="margin-bottom:0;">
            <label for="mes" class="form-label">Mês de Referência</label>
            <input type="month" id="mes" name="mes" class="form-control" value="<?= htmlspecialchars($filtroMes) ?>">
        </div>

        <div class="form-group" style="margin-bottom:0;">
            <label for="modelo_id" class="form-label">Modelo de Produto</label>
            <select id="modelo_id" name="modelo_id" class="form-control">
                <option value="">-- Todos os Modelos --</option>
                <?php foreach ($modelos as $m): ?>
                    <option value="<?= $m['id'] ?>" <?= $filtroModelo == $m['id'] ? 'selected' : '' ?>>[<?= htmlspecialchars($m['referencia']) ?>] <?= htmlspecialchars($m['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin-bottom:0;">
            <label for="oficina_id" class="form-label">Oficina / Facção</label>
            <select id="oficina_id" name="oficina_id" class="form-control">
                <option value="">-- Todas as Oficinas --</option>
                <?php foreach ($oficinas as $of): ?>
                    <option value="<?= $of['id'] ?>" <?= $filtroOficina == $of['id'] ? 'selected' : '' ?>><?= htmlspecialchars($of['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display:flex; gap:10px;">
            <button type="submit" class="btn btn-secondary" style="flex:1;"><i data-lucide="search" style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-right:4px;"></i> Filtrar</button>
            <?php if (!empty($filtroMes) || $filtroModelo || $filtroOficina): ?>
                <a href="/relatorios/perdas" class="btn btn-secondary">Limpar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Scorecards de Perdas -->
<div class="dashboard-grid" style="grid-template-columns: 1fr 1fr; margin-bottom: 25px;">
    <div class="stat-card stat-danger">
        <span class="stat-title">Total de Peças Perdidas</span>
        <span class="stat-value"><?= number_format($totalPecas, 0, ',', '.') ?> pçs</span>
        <span class="stat-subtext">Unidades descartadas ou com defeitos</span>
    </div>
    <div class="stat-card stat-danger">
        <span class="stat-title">Impacto Financeiro das Perdas</span>
        <span class="stat-value">R$ <?= number_format($totalValor, 2, ',', '.') ?></span>
        <span class="stat-subtext">Custo acumulado de matéria-prima + costura</span>
    </div>
</div>

<!-- Tabela de OPs e Lançamentos de Perdas -->
<div class="card-box">
    <div class="card-title-box">
        <h3>Detalhamento das Perdas Produtivas</h3>
    </div>

    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>OP</th>
                    <th>Modelo de Produto</th>
                    <th>Oficina / Facção</th>
                    <th>Qtd. Perdida</th>
                    <th>Custo Unitário Peça</th>
                    <th>Valor Total Perdido</th>
                    <th>Data Retorno</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($perdas)): ?>
                    <tr>
                        <td colspan="7" class="empty-state">
                            Nenhum registro de perda encontrado com os filtros aplicados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($perdas as $p): ?>
                        <tr>
                            <td><strong>OP #<?= $p['op_id'] ?></strong></td>
                            <td>
                                <strong style="color:var(--primary);"><?= htmlspecialchars($p['referencia']) ?></strong>
                                <div style="font-size:12px; color:var(--muted);"><?= htmlspecialchars($p['modelo_nome']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($p['oficina_nome']) ?></td>
                            <td style="color:var(--danger); font-weight:700;"><?= number_format($p['quantidade_perdida'], 0, ',', '.') ?> pçs</td>
                            <td>R$ <?= number_format($p['custo_unitario'], 2, ',', '.') ?></td>
                            <td style="color:var(--danger); font-weight:700;">R$ <?= number_format($p['valor_perdido'], 2, ',', '.') ?></td>
                            <td><?= date('d/m/Y', strtotime($p['data_retorno'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
