<?php require __DIR__ . '/../layouts/header.php'; ?>

<!-- Linha 1 de Cards -->
<div class="dashboard-grid">
    <!-- Produtos Acabados -->
    <div class="stat-card stat-primary">
        <span class="stat-title">Modelos Ativos</span>
        <span class="stat-value"><?= $modelosAtivos ?></span>
        <span class="stat-subtext">Modelos cadastrados ativos</span>
    </div>

    <!-- Matérias-Primas -->
    <div class="stat-card stat-info">
        <span class="stat-title">Itens em Estoque</span>
        <span class="stat-value"><?= $itensEstoque ?></span>
        <span class="stat-subtext">Itens de matéria-prima cadastrados</span>
    </div>

    <!-- OPs em Andamento com Progresso Carga de Fábrica -->
    <div class="stat-card stat-success">
        <span class="stat-title">OPs em Andamento</span>
        <span class="stat-value"><?= $opsAndamento ?></span>
        <div class="stat-subtext">Carga de Fábrica: <?= $cargaFabrica ?>%</div>
        <div class="progress-bar-container">
            <div class="progress-bar" style="width: <?= min($cargaFabrica, 100) ?>%"></div>
        </div>
    </div>

    <!-- Valor em Estoque -->
    <div class="stat-card stat-warning">
        <span class="stat-title">Valor em Estoque</span>
        <span class="stat-value">R$ <?= number_format($valorEstoqueTotal, 2, ',', '.') ?></span>
        <span class="stat-subtext">M.P. + Produto Acabado</span>
    </div>
</div>

<!-- Linha 2 de Cards -->
<div class="dashboard-grid">
    <!-- Total Produzido -->
    <div class="stat-card stat-primary">
        <span class="stat-title">Total Produzido</span>
        <span class="stat-value"><?= $totalProduzido > 0 ? number_format($totalProduzido, 0, ',', '.') : 'Sem Histórico' ?></span>
        <span class="stat-subtext">Peças boas retornadas</span>
    </div>

    <!-- Total Perdido -->
    <div class="stat-card stat-danger">
        <span class="stat-title">Total Perdido</span>
        <span class="stat-value"><?= number_format($totalPerdido, 0, ',', '.') ?></span>
        <span class="stat-subtext">Peças com defeito</span>
    </div>

    <!-- Valor das Perdas -->
    <div class="stat-card stat-danger">
        <span class="stat-title">Valor das Perdas</span>
        <span class="stat-value">R$ <?= number_format($valorPerdas, 2, ',', '.') ?></span>
        <span class="stat-subtext">Custo financeiro perdido</span>
    </div>

    <!-- Eficiência Produtiva com Progresso -->
    <div class="stat-card stat-success">
        <span class="stat-title">Eficiência Produtiva</span>
        <span class="stat-value"><?= $eficiencia ?>%</span>
        <div class="stat-subtext">Meta: 95%</div>
        <div class="progress-bar-container">
            <div class="progress-bar success" style="width: <?= min($eficiencia, 100) ?>%"></div>
        </div>
    </div>
</div>

<!-- Linha 3: OPs Recentes vs Movimentações de Estoque -->
<div class="layout-split">
    <!-- Bloco 1: Ordens de Produção Recentes -->
    <div class="card-box">
        <div class="card-title-box">
            <h3>Ordens de Produção Recentes</h3>
            <a href="/ops" class="action-link">Ver Todas</a>
        </div>
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Cód. / Modelo</th>
                        <th>Quantidade</th>
                        <th>Prazo</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($opsRecentes)): ?>
                        <tr>
                            <td colspan="4" class="empty-state">
                                Nenhuma OP cadastrada.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($opsRecentes as $op): ?>
                            <tr>
                                <td>
                                    <strong>OP #<?= $op['id'] ?></strong>
                                    <div style="font-size: 12px; color: var(--muted);"><?= htmlspecialchars($op['modelo_nome']) ?> (<?= htmlspecialchars($op['referencia']) ?>)</div>
                                </td>
                                <td><?= number_format($op['quantidade'], 0, ',', '.') ?> pçs</td>
                                <td><?= date('d/m/Y', strtotime($op['prazo'])) ?></td>
                                <td>
                                    <?php if ($op['status'] === 'aberta'): ?>
                                        <span class="badge badge-secondary">Aberta</span>
                                    <?php elseif ($op['status'] === 'em andamento'): ?>
                                        <span class="badge badge-info">Em Andamento</span>
                                    <?php elseif ($op['status'] === 'concluída'): ?>
                                        <span class="badge badge-success">Concluída</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Cancelada</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bloco 2: Movimentações de Estoque -->
    <div class="card-box">
        <div class="card-title-box">
            <h3>Últimas Movimentações de Estoque</h3>
            <a href="/estoque/ajuste" class="action-link">Ajustar</a>
        </div>
        <div class="table-responsive">
            <table class="table-custom">
                <colgroup>
                    <col style="width: 35%;">
                    <col style="width: 18%;">
                    <col style="width: 22%;">
                    <col style="width: 25%;">
                </colgroup>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qtd.</th>
                        <th>Tipo</th>
                        <th>Motivo / Usuário</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($movimentacoesRecentes)): ?>
                        <tr>
                            <td colspan="4" class="empty-state">
                                Nenhuma movimentação.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($movimentacoesRecentes as $mov): ?>
                            <tr>
                                <td>
                                    <strong style="color: #334155;"><?= htmlspecialchars($mov['item_nome']) ?></strong>
                                    <div style="font-size:11px; color: var(--muted); text-transform:capitalize;"><?= $mov['tipo_item'] === 'materia_prima' ? 'Matéria-Prima' : 'Produto Acabado' ?></div>
                                </td>
                                <td><strong><?= number_format($mov['quantidade'], 2, ',', '.') ?></strong></td>
                                <td>
                                    <?php if ($mov['tipo_movimentacao'] === 'entrada'): ?>
                                        <span class="badge badge-success" style="font-weight: 700;">+ ENTRADA</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger" style="font-weight: 700;">- SAÍDA</span>
                                    <?php endif; ?>
                                </td>
                                <td style="white-space: normal; max-width: 0;">
                                    <div style="font-size:12px; font-weight:600; white-space: normal; word-break: break-word;"><?= htmlspecialchars($mov['motivo']) ?></div>
                                    <div style="font-size:11px; color: var(--muted);"><?= htmlspecialchars($mov['usuario_nome'] ?? 'Sistema') ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Linha 4: Tabela Full Width de Produção por Período -->
<div class="card-box">
    <div class="card-title-box">
        <h3>Produção por Período</h3>
    </div>
    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Período (Mês/Ano)</th>
                    <th>Quantidade Produzida (Peças Boas)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($producaoPeriodo)): ?>
                    <tr>
                        <td colspan="2" class="empty-state">
                            Nenhum dado produtivo.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($producaoPeriodo as $p): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($p['periodo']) ?></strong></td>
                            <td><strong><?= number_format($p['quantidade_produzida'], 0, ',', '.') ?> pçs</strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
