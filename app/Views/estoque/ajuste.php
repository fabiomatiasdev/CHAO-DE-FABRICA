<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="layout-split" style="grid-template-columns: 1fr 2fr;">
    <!-- Formulário de Ajuste -->
    <div class="card-box">
        <div class="card-title-box">
            <h3>Lançar Ajuste Manual</h3>
        </div>

        <form action="/estoque/ajuste" method="POST">
            <!-- Tipo de Item -->
            <div class="form-group">
                <label for="tipo_item" class="form-label">Tipo de Item *</label>
                <select id="tipo_item" name="tipo_item" class="form-control" required>
                    <option value="materia_prima">Matéria-Prima / Insumo</option>
                    <option value="produto_acabado">Produto Acabado (Modelo)</option>
                </select>
            </div>

            <!-- Item ID (Carregado dinamicamente via JS) -->
            <div class="form-group">
                <label for="item_id" class="form-label">Selecionar Item *</label>
                <select id="item_id" name="item_id" class="form-control" required>
                    <!-- Preenchido por script -->
                </select>
                <span id="estoque-atual-helper" style="font-size: 11px; color: var(--muted); display: block; margin-top: 4px;">Estoque atual: -</span>
            </div>

            <!-- Local de Estoque (Armazenador) -->
            <div class="form-group">
                <label for="local_estoque_id" class="form-label">Local de Armazenamento (Estoque)</label>
                <select id="local_estoque_id" name="local_estoque_id" class="form-control">
                    <option value="">-- Selecione o Local de Estoque --</option>
                    <?php if (!empty($locaisEstoque)): ?>
                        <?php foreach ($locaisEstoque as $loc): ?>
                            <option value="<?= $loc['id'] ?>">
                                <?= htmlspecialchars($loc['nome']) ?> (<?= ucfirst($loc['tipo']) ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="tipo_movimentacao" class="form-label">Movimentação *</label>
                    <select id="tipo_movimentacao" name="tipo_movimentacao" class="form-control" required>
                        <option value="entrada">+ Entrada de Inventário</option>
                        <option value="saída">- Saída de Inventário</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="quantidade" class="form-label">Quantidade *</label>
                    <input type="number" id="quantidade" name="quantidade" class="form-control" step="0.01" min="0.01" placeholder="0.00" required>
                </div>
            </div>

            <div class="form-group">
                <label for="motivo" class="form-label">Motivo / Justificativa *</label>
                <input type="text" id="motivo" name="motivo" class="form-control" placeholder="Ex: Ajuste de contagem, Perda por umidade" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block"><i data-lucide="sliders" style="width:16px;height:16px;"></i> Executar Ajuste</button>
        </form>
    </div>

    <!-- Tabela de Histórico de Movimentações -->
    <div class="card-box">
        <div class="card-title-box">
            <h3>Histórico de Movimentações de Estoque</h3>
        </div>

        <div class="table-responsive" style="width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
            <table class="table-custom" style="min-width: 650px;">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Local</th>
                        <th>Tipo Item</th>
                        <th>Qtd. Ajustada</th>
                        <th>Operação</th>
                        <th>Justificativa / Usuário</th>
                        <th>Data/Hora</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($ajustes)): ?>
                        <tr>
                            <td colspan="7" class="empty-state">

                                Nenhuma movimentação registrada.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ajustes as $aj): ?>
                            <tr>
                                <td><strong style="color: #334155;"><?= htmlspecialchars($aj['item_nome']) ?></strong></td>
                                <td><span class="badge" style="background:#e2e8f0; color:#334155; font-size:11px;"><?= htmlspecialchars($aj['local_nome'] ?? 'Geral') ?></span></td>
                                <td>

                                    <?php if ($aj['tipo_item'] === 'materia_prima'): ?>
                                        <span class="badge badge-secondary">Matéria-Prima</span>
                                    <?php else: ?>
                                        <span class="badge badge-info">P. Acabado</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= number_format($aj['quantidade'], 2, ',', '.') ?></strong></td>
                                <td>
                                    <?php if ($aj['tipo_movimentacao'] === 'entrada'): ?>
                                        <span class="badge badge-success" style="font-weight: 700;">+ ENTRADA</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger" style="font-weight: 700;">- SAÍDA</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-size:13px; font-weight: 600;"><?= htmlspecialchars($aj['motivo']) ?></div>
                                    <div style="font-size:11px; color: var(--muted);"><?= htmlspecialchars($aj['usuario_nome'] ?? 'Sistema') ?></div>
                                </td>
                                <td><span style="font-size:12px; color:var(--muted);"><?= date('d/m/Y H:i', strtotime($aj['criado_em'])) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php require __DIR__ . '/../layouts/pagination.php'; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectTipo = document.getElementById('tipo_item');
        const selectItem = document.getElementById('item_id');
        const helper = document.getElementById('estoque-atual-helper');

        // Dados de matérias-primas e produtos mapeados para JS
        const materias = <?= json_encode($materias) ?>;
        const produtos = <?= json_encode($produtos) ?>;

        function updateItemOptions() {
            const tipo = selectTipo.value;
            selectItem.innerHTML = '';
            
            const list = (tipo === 'materia_prima') ? materias : produtos;

            list.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.id;
                
                let text = item.nome;
                if (item.referencia) {
                    text = "[" + item.referencia + "] " + item.nome;
                }
                
                opt.textContent = text;
                opt.setAttribute('data-estoque', item.estoque_atual);
                opt.setAttribute('data-um', item.unidade_medida || 'pçs');
                selectItem.appendChild(opt);
            });

            updateEstoqueHelper();
        }

        function updateEstoqueHelper() {
            const selectedOpt = selectItem.options[selectItem.selectedIndex];
            if (selectedOpt) {
                const est = parseFloat(selectedOpt.getAttribute('data-estoque'));
                const um = selectedOpt.getAttribute('data-um');
                helper.innerHTML = "Estoque atual: <strong>" + est.toLocaleString('pt-BR', {minimumFractionDigits: 2}) + " " + um + "</strong>";
            } else {
                helper.innerHTML = "Estoque atual: -";
            }
        }

        selectTipo.addEventListener('change', updateItemOptions);
        selectItem.addEventListener('change', updateEstoqueHelper);

        // Inicializar opções
        updateItemOptions();
    });
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
