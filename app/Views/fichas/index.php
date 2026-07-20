<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="card-box">
    <div class="card-title-box">
        <h3>Fichas Técnicas</h3>
        <a href="/fichas/novo" class="btn btn-primary"><i data-lucide="plus"></i> Nova Ficha Técnica</a>
    </div>

    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Referência</th>
                    <th>Modelo</th>
                    <th class="text-right">Tempo Padrão</th>
                    <th class="text-right">Custo Mão de Obra</th>
                    <th class="text-right">Custo Matéria-Prima</th>
                    <th class="text-right">Custo Total Estimado</th>
                    <th>Data Cadastro</th>
                    <th class="text-right">Ações</th>
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
                            <td class="text-right"><?= htmlspecialchars($f['tempo_padrao']) ?> minutos</td>
                            <td class="text-right">R$ <?= number_format($f['custo_mao_obra'], 2, ',', '.') ?></td>
                            <td class="text-right">R$ <?= number_format($f['custo_materia_prima'], 2, ',', '.') ?></td>
                            <td class="text-right" style="font-weight: 700; color: var(--primary);">R$
                                <?= number_format($f['custo_total_estimado'], 2, ',', '.') ?></td>
                            <td><?= date('d/m/Y', strtotime($f['criado_em'])) ?></td>
                            <td class="text-right">
                                <div class="actions-cell">
                                    <a href="/fichas/editar?id=<?= $f['id'] ?>" class="btn btn-secondary btn-sm">
                                        <i data-lucide="edit-2"></i> Editar
                                    </a>
                                    <a href="/fichas/excluir?id=<?= $f['id'] ?>" class="btn btn-danger btn-sm"
                                        onclick="return confirm('Deseja realmente excluir esta ficha técnica?');">
                                        <i data-lucide="trash-2"></i> Excluir
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>