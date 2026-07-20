<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="card-box">
    <div class="card-title-box">
        <h3>Modelos de Produtos Cadastrados</h3>
        <a href="/produtos/novo" class="btn btn-primary"><i data-lucide="plus"></i> Novo Modelo</a>
    </div>

    <!-- Barra de Filtros/Busca -->
    <form action="/produtos" method="GET" style="margin-bottom: 20px; display: flex; gap: 10px;">
        <input type="text" name="busca" class="form-control" placeholder="Buscar por Nome, Referência ou Categoria..." value="<?= htmlspecialchars($busca ?? '') ?>" style="max-width: 400px;">
        <button type="submit" class="btn btn-secondary"><i data-lucide="search"></i> Filtrar</button>
        <?php if (!empty($busca)): ?>
            <a href="/produtos" class="btn btn-secondary">Limpar</a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Imagem</th>
                    <th>Referência</th>
                    <th>Nome do Modelo</th>
                    <th>Categoria</th>
                    <th>Cor</th>
                    <th class="text-center">Grade de Tamanhos</th>
                    <th class="text-center">Status</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($modelos)): ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            <i data-lucide="shirt" style="width:32px;height:32px;"></i>
                            <p>Nenhum modelo de produto encontrado.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($modelos as $m): ?>
                        <tr>
                            <td>
                                <?php if ($m['imagem']): ?>
                                    <img src="<?= htmlspecialchars($m['imagem']) ?>" alt="Imagem" style="width: 48px; height: 48px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border);">
                                <?php else: ?>
                                    <div style="width: 48px; height: 48px; background-color: #f1f5f9; color: #94a3b8; display: flex; align-items: center; justify-content: center; border-radius: 6px; font-weight:600; font-size:12px;">N/A</div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($m['referencia']) ?></strong></td>
                            <td><?= htmlspecialchars($m['nome']) ?></td>
                            <td><?= htmlspecialchars($m['categoria']) ?></td>
                            <td>
                                <?php 
                                $cores = array_unique(array_filter(array_map('trim', explode(',', $m['cor']))));
                                if (count($cores) > 3) {
                                    $exibidas = array_slice($cores, 0, 2);
                                    $restantes = count($cores) - 2;
                                    echo htmlspecialchars(implode(', ', $exibidas)) . ' <span style="font-size:11px; color:var(--muted); font-weight:600;">(+' . $restantes . ' cores)</span>';
                                } else {
                                    echo htmlspecialchars(implode(', ', $cores));
                                }
                                ?>
                            </td>
                            <td class="text-center">
                                <?php 
                                $grades = explode(',', $m['grade_tamanhos']);
                                $gradesLimpos = array_unique(array_filter(array_map(function($val) {
                                    return strtoupper(trim($val));
                                }, $grades)));
                                
                                // Ordenação lógica de tamanhos de vestuário
                                $ordemPadraoTamanhos = ['XP', 'PP', 'P', 'M', 'G', 'GG', 'XG', 'EG', 'EGG', 'G1', 'G2', 'G3', 'G4', 'G5'];
                                usort($gradesLimpos, function($a, $b) use ($ordemPadraoTamanhos) {
                                    $posA = array_search($a, $ordemPadraoTamanhos);
                                    $posB = array_search($b, $ordemPadraoTamanhos);
                                    if ($posA !== false && $posB !== false) {
                                        return $posA <=> $posB;
                                    }
                                    if ($posA !== false) return -1;
                                    if ($posB !== false) return 1;
                                    return strnatcasecmp($a, $b);
                                });
                                
                                foreach ($gradesLimpos as $g) {
                                    echo '<span style="background-color: #f1f5f9; color: #475569; padding: 2px 6px; border-radius: 4px; font-size: 11px; margin-right: 4px; font-weight: 600;">' . htmlspecialchars($g) . '</span>';
                                }
                                ?>
                            </td>
                            <td class="text-center">
                                <?php if ($m['status'] === 'ativo'): ?>
                                    <span class="badge badge-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                <div class="actions-cell">
                                    <a href="/produtos/editar?id=<?= $m['id'] ?>" class="btn btn-secondary btn-sm">
                                        <i data-lucide="edit-2"></i> Editar
                                    </a>
                                    <a href="/produtos/excluir?id=<?= $m['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Deseja realmente excluir este modelo?');">
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
