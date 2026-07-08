<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="card-box">
    <div class="card-title-box">
        <h3>Fichas Técnicas (BOM - Bill of Materials)</h3>
        <a href="/fichas/novo" class="btn btn-primary"><i data-lucide="plus"></i> Nova Ficha Técnica</a>
    </div>

    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Referência</th>
                    <th>Modelo</th>
                    <th>Tempo Padrão</th>
                    <th>Custo Mão de Obra</th>
                    <th>Custo Matéria-Prima</th>
                    <th>Custo Total Estimado</th>
                    <th>Data Cadastro</th>
                    <th style="text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($fichas)): ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            <i data-lucide="file-text" style="width:32px;height:32px;"></i>
                            <p>Nenhuma ficha técnica cadastrada ainda.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($fichas as $f): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($f['referencia']) ?></strong></td>
                            <td>
                                <div><?= htmlspecialchars($f['modelo_nome']) ?></div>
                                <span style="font-size:11px;color:var(--muted);">Cor: <?= htmlspecialchars($f['cor']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($f['tempo_padrao']) ?> minutos</td>
                            <td>R$ <?= number_format($f['custo_mao_obra'], 2, ',', '.') ?></td>
                            <td>R$ <?= number_format($f['custo_materia_prima'], 2, ',', '.') ?></td>
                            <td style="font-weight: 700; color: var(--primary);">R$ <?= number_format($f['custo_total_estimado'], 2, ',', '.') ?></td>
                            <td><?= date('d/m/Y', strtotime($f['criado_em'])) ?></td>
                            <td style="text-align: right;" class="actions-cell">
                                <a href="/fichas/editar?id=<?= $f['id'] ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">
                                    <i data-lucide="edit-2" style="width: 14px; height: 14px;"></i> Editar
                                </a>
                                <a href="/fichas/excluir?id=<?= $f['id'] ?>" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;" onclick="return confirm('Deseja realmente excluir esta ficha técnica?');">
                                    <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i> Excluir
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
