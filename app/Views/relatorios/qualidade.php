<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="layout-split" style="grid-template-columns: 1fr 2fr;">
    <!-- Formulário de Inspeção de Qualidade -->
    <div class="card-box">
        <div class="card-title-box">
            <h3>Nova Inspeção de Qualidade</h3>
        </div>

        <form action="/relatorios/qualidade/novo" method="POST">
            <div class="form-group">
                <label for="ordem_producao_id" class="form-label">Ordem de Produção (OP) *</label>
                <select id="ordem_producao_id" name="ordem_producao_id" class="form-control" required>
                    <option value="">-- Selecione a OP --</option>
                    <?php foreach ($ops as $op): ?>
                        <option value="<?= $op['id'] ?>">[OP #<?= $op['id'] ?>] <?= htmlspecialchars($op['referencia']) ?> - <?= htmlspecialchars($op['modelo_nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="quantidade_aprovada" class="form-label">Qtd. Aprovada *</label>
                    <input type="number" id="quantidade_aprovada" name="quantidade_aprovada" class="form-control" min="0" placeholder="Ex: 195" required>
                </div>

                <div class="form-group">
                    <label for="quantidade_reprovada" class="form-label">Qtd. Reprovada *</label>
                    <input type="number" id="quantidade_reprovada" name="quantidade_reprovada" class="form-control" min="0" placeholder="Ex: 5" required>
                </div>
            </div>

            <div class="form-group">
                <label for="tipo_defeito" class="form-label">Tipo de Defeito (Se houver)</label>
                <input type="text" id="tipo_defeito" name="tipo_defeito" class="form-control" placeholder="Ex: Costura aberta, manchas de óleo, etc.">
            </div>

            <div class="form-group">
                <label for="responsavel" class="form-label">Inspetor Responsável *</label>
                <input type="text" id="responsavel" name="responsavel" class="form-control" placeholder="Ex: Ana Qualidade" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block"><i data-lucide="check-square" style="width:16px;height:16px;"></i> Registrar Inspeção</button>
        </form>
    </div>

    <!-- Tabela de Lançamentos de Inspeções -->
    <div class="card-box">
        <div class="card-title-box">
            <h3>Inspeções de Qualidade Registradas</h3>
        </div>

        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>OP</th>
                        <th>Modelo / Referência</th>
                        <th>Aprovadas</th>
                        <th>Reprovadas</th>
                        <th>Tipo de Defeito</th>
                        <th>Responsável</th>
                        <th>Data/Hora</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inspecoes)): ?>
                        <tr>
                            <td colspan="7" class="empty-state">
                                Nenhuma inspeção de qualidade registrada.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($inspecoes as $ins): ?>
                            <tr>
                                <td><strong>OP #<?= $ins['op_id'] ?></strong></td>
                                <td>
                                    <strong style="color:var(--primary);"><?= htmlspecialchars($ins['referencia']) ?></strong>
                                    <div style="font-size:12px; color:var(--muted);"><?= htmlspecialchars($ins['modelo_nome']) ?></div>
                                </td>
                                <td style="color:var(--success); font-weight:700;"><?= number_format($ins['quantidade_aprovada'], 0, ',', '.') ?> pçs</td>
                                <td style="color:var(--danger); font-weight:700;"><?= number_format($ins['quantidade_reprovada'], 0, ',', '.') ?> pçs</td>
                                <td><?= htmlspecialchars($ins['tipo_defeito'] ?: 'Nenhum') ?></td>
                                <td><?= htmlspecialchars($ins['responsavel']) ?></td>
                                <td><span style="font-size:12px; color:var(--muted);"><?= date('d/m/Y H:i', strtotime($ins['criado_em'])) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php require __DIR__ . '/../layouts/pagination.php'; ?>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
