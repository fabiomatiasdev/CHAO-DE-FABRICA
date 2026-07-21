<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="card-box">
    <div class="card-title-box">
        <h3>Oficinas & Facções Parceiras</h3>
        <a href="/oficinas/novo" class="btn btn-primary"><i data-lucide="plus"></i> Nova Oficina</a>
    </div>

    <!-- Barra de Filtros/Busca -->
    <form action="/oficinas" method="GET" style="margin-bottom: 20px; display: flex; gap: 10px;">
        <input type="text" name="busca" class="form-control" placeholder="Buscar por Nome, CNPJ/CPF ou Contato..." value="<?= htmlspecialchars($busca ?? '') ?>" style="max-width: 400px;">
        <button type="submit" class="btn btn-secondary"><i data-lucide="search"></i> Filtrar</button>
        <?php if (!empty($busca)): ?>
            <a href="/oficinas" class="btn btn-secondary">Limpar</a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Nome da Oficina</th>
                    <th>CNPJ/CPF</th>
                    <th>Endereço</th>
                    <th>Contato</th>
                    <th class="text-right">Capacidade Produtiva</th>
                    <th class="text-right">Valor Mão de Obra / Peça</th>
                    <th class="text-center">Status</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($oficinas)): ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            <i data-lucide="factory" style="width:32px;height:32px;"></i>
                            <p>Nenhuma oficina cadastrada.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($oficinas as $of): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($of['nome']) ?></strong></td>
                            <td><?= htmlspecialchars($of['cnpj_cpf']) ?></td>
                            <td><?= htmlspecialchars($of['endereco'] ?: 'N/D') ?></td>
                            <td><?= htmlspecialchars($of['contato'] ?: 'N/D') ?></td>
                            <td class="text-right"><?= number_format($of['capacidade_produtiva'], 0, ',', '.') ?> peças/mês</td>
                            <td class="text-right" style="font-weight: 600;">R$ <?= number_format($of['mao_obra_peca'], 2, ',', '.') ?></td>
                            <td class="text-center">
                                <?php if ($of['status'] === 'ativo'): ?>
                                    <span class="badge badge-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                <div class="actions-cell">
                                    <a href="/oficinas/editar?id=<?= $of['id'] ?>" class="btn btn-secondary btn-sm">
                                        <i data-lucide="edit-2"></i> Editar
                                    </a>
                                    <a href="/oficinas/excluir?id=<?= $of['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Deseja realmente excluir esta oficina?');">
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

    <?php require __DIR__ . '/../layouts/pagination.php'; ?>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
