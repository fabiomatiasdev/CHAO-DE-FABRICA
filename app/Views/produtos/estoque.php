<?php require __DIR__ . '/../layouts/header.php'; ?>

<?php
// Agrupar variantes por modelo no PHP
$modelosAgrupados = [];
foreach ($variantes as $v) {
    $modeloId = $v['produto_modelo_id'];
    if (!isset($modelosAgrupados[$modeloId])) {
        $modelosAgrupados[$modeloId] = [
            'referencia' => $v['referencia'],
            'nome' => $v['modelo_nome'],
            'categoria' => $v['categoria'],
            'estoque_total' => 0,
            'variantes' => []
        ];
    }
    $modelosAgrupados[$modeloId]['estoque_total'] += $v['estoque_atual'];
    $modelosAgrupados[$modeloId]['variantes'][] = $v;
}
?>

<div class="card-box" style="margin-bottom: 25px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
        <div class="card-title-box" style="margin-bottom:0;">
            <h3>Grade de Estoque de Produtos Acabados</h3>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div style="margin-top:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; border-bottom:1px solid var(--border); padding-bottom:15px;">
        <div style="display:flex; gap:10px; flex: 1; max-width: 500px;">
            <input type="text" id="filtro-busca" class="form-control" placeholder="Buscar por modelo ou referência..." style="font-size:14px;">
        </div>
        <div style="font-size:13px; color:var(--muted);">
            Exibindo <strong id="lbl-total-itens" style="color:#334155;"><?= count($modelosAgrupados) ?></strong> modelos no total.
        </div>
    </div>

    <!-- Tabela de Modelos (Acordeão) -->
    <div class="table-responsive" style="margin-top:15px;">
        <table class="table-custom" id="tabela-estoque-acabados">
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;"></th>
                    <th style="width: 150px;">Referência</th>
                    <th>Modelo de Produto</th>
                    <th>Categoria</th>
                    <th style="width: 180px; text-align: center;">Variações Cadastradas</th>
                    <th style="width: 180px; text-align: right; padding-right: 20px;">Estoque Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($modelosAgrupados)): ?>
                    <tr class="sem-resultados">
                        <td colspan="6" style="text-align: center; color: var(--muted); padding: 30px 0;">
                            <i data-lucide="info" style="width: 24px; height: 24px; display: block; margin: 0 auto 10px; color: var(--primary);"></i>
                            Nenhum produto cadastrado com variantes de estoque no sistema.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($modelosAgrupados as $modeloId => $m): ?>
                        <!-- Linha Principal do Modelo -->
                        <tr class="linha-modelo" style="cursor: pointer; transition: background-color 0.2s;" data-id="<?= $modeloId ?>" data-busca="<?= strtolower(htmlspecialchars($m['nome'] . ' ' . $m['referencia'])) ?>" title="Clique para expandir as variações">
                            <td style="text-align: center;">
                                <i data-lucide="chevron-right" class="chevron-icone" id="chevron-<?= $modeloId ?>" style="transition: transform 0.2s; width: 16px; height: 16px; color: var(--primary);"></i>
                            </td>
                            <td><strong><?= htmlspecialchars($m['referencia']) ?></strong></td>
                            <td><strong><?= htmlspecialchars($m['nome']) ?></strong></td>
                            <td><span class="badge badge-secondary" style="font-weight:normal;"><?= htmlspecialchars($m['categoria']) ?></span></td>
                            <td style="text-align: center; color: var(--muted); font-size: 13px;"><?= count($m['variantes']) ?> variações</td>
                            <td style="text-align: right; padding-right: 20px;">
                                <strong style="font-size:15px; color: <?= $m['estoque_total'] <= 0 ? 'var(--danger)' : '#1e293b' ?>;">
                                    <?= htmlspecialchars($m['estoque_total']) ?>
                                </strong>
                            </td>
                        </tr>

                        <!-- Linha de Detalhe das Variantes (Oculta por Padrão) -->
                        <tr id="variantes-container-<?= $modeloId ?>" class="container-variante-linha" style="display: none; background-color: #f8fafc;">
                            <td colspan="6" style="padding: 12px 20px;">
                                <div style="border: 1px solid var(--border); border-radius: 8px; background-color: #ffffff; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                                    
                                    <?php
                                    // Agrupar variantes por cor no PHP
                                    $variantesPorCor = [];
                                    foreach ($m['variantes'] as $v) {
                                        $cor = strtoupper(trim($v['cor']));
                                        if (!isset($variantesPorCor[$cor])) {
                                            $variantesPorCor[$cor] = [];
                                        }
                                        $variantesPorCor[$cor][] = $v;
                                    }
                                    ksort($variantesPorCor);
                                    ?>

                                    <!-- Abas por Cor (Notion/Linear Style) -->
                                    <div class="cores-tabs-header" style="display: flex; gap: 8px; margin: 15px; border-bottom: 1px solid var(--border); padding-bottom: 12px; flex-wrap: wrap;">
                                        <?php 
                                        $firstCor = true;
                                        foreach ($variantesPorCor as $cor => $itensCor): 
                                            $totalEstoqueCor = array_sum(array_column($itensCor, 'estoque_atual'));
                                            $safeCorId = preg_replace('/[^A-Za-z0-9]/', '', $cor);
                                            
                                            // Estilos da aba
                                            $borderStyle = $firstCor ? 'var(--primary)' : 'var(--border)';
                                            $bgStyle = $firstCor ? 'var(--primary)' : '#f8fafc';
                                            $colorStyle = $firstCor ? 'white' : 'var(--muted)';
                                            $activeClass = $firstCor ? 'active' : '';
                                        ?>
                                            <button type="button" class="tab-cor-btn <?= $activeClass ?>" 
                                                    data-modelo="<?= $modeloId ?>" 
                                                    data-cor-id="<?= $safeCorId ?>"
                                                    onclick="switchTabCor(this, '<?= $modeloId ?>', '<?= $safeCorId ?>')"
                                                    style="padding: 6px 12px; border-radius: 6px; border: 1px solid <?= $borderStyle ?>; background-color: <?= $bgStyle ?>; font-size: 13px; font-weight: 600; cursor: pointer; color: <?= $colorStyle ?>; transition: all 0.15s ease; display: inline-flex; align-items: center; gap: 6px;">
                                                <?= htmlspecialchars($cor) ?>
                                                <span class="badge" style="font-size: 10px; padding: 2px 5px; background: <?= $firstCor ? 'rgba(255,255,255,0.2)' : '#e2e8f0' ?>; color: <?= $firstCor ? 'white' : '#475569' ?>;"><?= $totalEstoqueCor ?> pçs</span>
                                            </button>
                                        <?php 
                                            $firstCor = false;
                                        endforeach; 
                                        ?>
                                    </div>

                                    <!-- Conteúdo das Abas (Tabelas de Tamanhos por Cor) -->
                                    <?php 
                                    $firstCor = true;
                                    foreach ($variantesPorCor as $cor => $itensCor): 
                                        $safeCorId = preg_replace('/[^A-Za-z0-9]/', '', $cor);
                                        $display = $firstCor ? 'block' : 'none';
                                    ?>
                                        <div class="tab-cor-content-<?= $modeloId ?>" id="tab-content-<?= $modeloId ?>-<?= $safeCorId ?>" style="display: <?= $display ?>; padding: 0 15px 15px 15px;">
                                            <div style="border: 1px solid var(--border); border-radius: 6px; overflow: hidden;">
                                                <table class="table-custom" style="margin: 0; font-size: 13px; width: 100%;">
                                                    <thead style="background-color: #f8fafc; border-bottom: 1px solid var(--border);">
                                                        <tr>
                                                            <th style="padding: 10px 12px; text-align: center; width: 100px;">Tamanho</th>
                                                            <th style="width: 150px; text-align: right; padding: 10px 12px;">Estoque Mínimo</th>
                                                            <th style="width: 150px; text-align: right; padding: 10px 12px;">Estoque Atual</th>
                                                            <th style="width: 120px; text-align: center; padding: 10px 12px;">Status</th>
                                                            <th style="width: 120px; text-align: center; padding: 10px 12px;">Ações</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($itensCor as $v): ?>
                                                            <?php 
                                                            $statusEstoque = 'normal';
                                                            $statusClass = 'badge-success';
                                                            $statusText = 'Em Dia';
                                                            
                                                            if ($v['estoque_atual'] <= 0) {
                                                                $statusEstoque = 'zerado';
                                                                $statusClass = 'badge-danger';
                                                                $statusText = 'Zerado';
                                                            } elseif ($v['estoque_atual'] < $v['estoque_minimo']) {
                                                                $statusEstoque = 'alerta';
                                                                $statusClass = 'badge-warning';
                                                                $statusText = 'Alerta';
                                                            }
                                                            ?>
                                                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                                                <td style="text-align: center; padding: 10px 12px;"><span class="badge badge-primary" style="font-weight: 600; min-width: 32px; justify-content: center;"><?= htmlspecialchars($v['tamanho']) ?></span></td>
                                                                <td style="text-align: right; color:#64748b; padding: 10px 12px;"><?= htmlspecialchars($v['estoque_minimo']) ?></td>
                                                                <td style="text-align: right; padding: 10px 12px;">
                                                                    <strong style="font-size: 14px; color: <?= $statusEstoque === 'zerado' ? 'var(--danger)' : ($statusEstoque === 'alerta' ? 'var(--warning)' : '#1e293b') ?>;">
                                                                        <?= htmlspecialchars($v['estoque_atual']) ?>
                                                                    </strong>
                                                                </td>
                                                                <td style="text-align: center; padding: 10px 12px;">
                                                                    <span class="badge <?= $statusClass ?>" style="font-size:10px; padding: 2px 6px;"><?= $statusText ?></span>
                                                                </td>
                                                                <td style="text-align: center; padding: 10px 12px;">
                                                                    <button type="button" class="btn btn-secondary btn-ajustar-rapido btn-sm" style="height: 26px; padding: 2px 8px;"
                                                                            data-id="<?= $v['id'] ?>"
                                                                            data-nome="<?= htmlspecialchars($m['nome'] . ' - ' . $v['cor'] . ' / ' . $v['tamanho']) ?>"
                                                                            data-estoque="<?= $v['estoque_atual'] ?>">
                                                                        <i data-lucide="sliders" style="width: 10px; height: 10px; display:inline-block; vertical-align:middle; margin-right:2px;"></i> Ajustar
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    <?php 
                                        $firstCor = false;
                                    endforeach; 
                                    ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de Ajuste Rápido de Estoque -->
<div id="modal-ajuste-estoque" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(15, 23, 42, 0.6); z-index:9999; justify-content:center; align-items:center; backdrop-filter: blur(4px);">
    <div class="card-box" style="width:100%; max-width: 450px; padding: 25px; margin: 15px; animation: modalFadeIn 0.3s ease-out; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border); padding-bottom:12px; margin-bottom:20px;">
            <h3 style="margin:0; font-size:16px; font-weight:700; color:#1e293b;">Ajuste Rápido de Estoque</h3>
            <button type="button" id="btn-fechar-modal" style="border:none; background:none; font-size:20px; color:#94a3b8; cursor:pointer;">&times;</button>
        </div>

        <form action="/produtos/estoque/ajuste" method="POST">
            <input type="hidden" name="variante_id" id="modal-variante-id">

            <div class="form-group">
                <label class="form-label" style="font-weight:600; font-size:12px; color:#475569;">Variante Escolhida</label>
                <div id="modal-variante-nome" style="background-color:#f1f5f9; padding:10px; border-radius:6px; font-weight:700; color:#334155; font-size:13px; border: 1px solid var(--border);">
                    -
                </div>
            </div>

            <div class="form-row" style="margin-top:15px;">
                <div class="form-group">
                    <label for="tipo_movimentacao" class="form-label">Operação *</label>
                    <select name="tipo_movimentacao" id="tipo_movimentacao" class="form-control" style="font-size:13px;" required>
                        <option value="entrada">Entrada (Adicionar estoque)</option>
                        <option value="saida">Saída (Retirar estoque)</option>
                    </select>
                </div>
                <div class="form-group" style="max-width: 120px;">
                    <label for="quantidade" class="form-label">Quantidade *</label>
                    <input type="number" name="quantidade" id="quantidade" class="form-control" min="1" required style="font-size:13px;" placeholder="Ex: 10">
                </div>
            </div>

            <div class="form-group" style="margin-top:15px;">
                <label for="motivo" class="form-label">Motivo do Ajuste *</label>
                <input type="text" name="motivo" id="motivo" class="form-control" placeholder="Ex: Acerto de inventário, perda de costura" style="font-size:13px;" required>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:25px; border-top:1px solid var(--border); padding-top:15px;">
                <button type="button" class="btn btn-secondary" id="btn-cancelar-modal" style="font-size:13px;">Cancelar</button>
                <button type="submit" class="btn btn-primary" style="font-size:13px;">Confirmar Ajuste</button>
            </div>
        </form>
    </div>
</div>

<style>
    @keyframes modalFadeIn {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    .linha-modelo:hover {
        background-color: #f8fafc;
    }
    .tab-cor-btn:hover {
        background-color: #e2e8f0 !important;
        border-color: #cbd5e1 !important;
        color: #1e293b !important;
    }
    .tab-cor-btn.active {
        background-color: var(--primary) !important;
        color: white !important;
        border-color: var(--primary) !important;
    }
    .tab-cor-btn.active:hover {
        background-color: var(--primary-hover) !important;
        border-color: var(--primary-hover) !important;
        color: white !important;
    }
</style>

<script>
    // Função global de chaveamento das abas de cores (Notion/Linear Style)
    function switchTabCor(btn, modeloId, corId) {
        // Obter todas as abas do mesmo modelo
        const botoes = document.querySelectorAll(`.tab-cor-btn[data-modelo="${modeloId}"]`);
        
        botoes.forEach(b => {
            b.classList.remove('active');
            b.style.backgroundColor = '#f8fafc';
            b.style.color = 'var(--muted)';
            b.style.borderColor = 'var(--border)';
            
            // Ajustar o badge interno correspondente
            const badge = b.querySelector('.badge');
            if (badge) {
                badge.style.background = '#e2e8f0';
                badge.style.color = '#475569';
            }
        });

        // Ativar aba clicada
        btn.classList.add('active');
        btn.style.backgroundColor = 'var(--primary)';
        btn.style.color = 'white';
        btn.style.borderColor = 'var(--primary)';
        
        const badgeAtivo = btn.querySelector('.badge');
        if (badgeAtivo) {
            badgeAtivo.style.background = 'rgba(255, 255, 255, 0.2)';
            badgeAtivo.style.color = 'white';
        }

        // Ocultar todas as tabelas deste modelo
        const conteudos = document.querySelectorAll(`.tab-cor-content-${modeloId}`);
        conteudos.forEach(c => c.style.display = 'none');

        // Exibir a tabela da cor correspondente
        const ativo = document.getElementById(`tab-content-${modeloId}-${corId}`);
        if (ativo) {
            ativo.style.display = 'block';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // --- FILTRAGEM RÁPIDA ---
        const inputBusca = document.getElementById('filtro-busca');
        const linhasModelos = document.querySelectorAll('.linha-modelo');
        const lblTotal = document.getElementById('lbl-total-itens');

        inputBusca.addEventListener('input', function() {
            const query = inputBusca.value.toLowerCase().trim();
            let visiveis = 0;

            linhasModelos.forEach(linha => {
                const texto = linha.getAttribute('data-busca');
                const id = linha.getAttribute('data-id');
                const containerVariantes = document.getElementById(`variantes-container-${id}`);
                const chevron = document.getElementById(`chevron-${id}`);

                if (texto.includes(query)) {
                    linha.style.display = '';
                    visiveis++;
                } else {
                    linha.style.display = 'none';
                    containerVariantes.style.display = 'none';
                    chevron.style.transform = 'rotate(0deg)';
                }
            });

            lblTotal.textContent = visiveis;
        });

        // --- CONTROLE DE COLAPSO (ACORDEÃO) ---
        linhasModelos.forEach(row => {
            row.addEventListener('click', function(e) {
                // Se clicou no botão Ajustar de alguma forma dentro da linha de detalhe, não interfere
                if (e.target.closest('button')) return;

                const id = row.getAttribute('data-id');
                const containerVariantes = document.getElementById(`variantes-container-${id}`);
                const chevron = document.getElementById(`chevron-${id}`);

                if (containerVariantes.style.display === 'none') {
                    containerVariantes.style.display = '';
                    chevron.style.transform = 'rotate(90deg)';
                } else {
                    containerVariantes.style.display = 'none';
                    chevron.style.transform = 'rotate(0deg)';
                }
            });
        });

        // --- CONTROLE DO MODAL ---
        const modal = document.getElementById('modal-ajuste-estoque');
        const btnFechar = document.getElementById('btn-fechar-modal');
        const btnCancelar = document.getElementById('btn-cancelar-modal');
        const inputVarianteId = document.getElementById('modal-variante-id');
        const containerVarianteNome = document.getElementById('modal-variante-nome');
        const inputQtd = document.getElementById('quantidade');
        const inputMotivo = document.getElementById('motivo');

        // Selecionar botões delegados para permitir escuta em abas dinâmicas
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-ajustar-rapido');
            if (btn) {
                e.stopPropagation(); // Evita colapsar o acordeão
                const id = btn.getAttribute('data-id');
                const nome = btn.getAttribute('data-nome');
                
                inputVarianteId.value = id;
                containerVarianteNome.textContent = nome;
                inputQtd.value = '';
                inputMotivo.value = '';
                
                modal.style.display = 'flex';
            }
        });

        function fecharModal() {
            modal.style.display = 'none';
        }

        btnFechar.addEventListener('click', fecharModal);
        btnCancelar.addEventListener('click', fecharModal);
        
        // Fechar ao clicar fora do modal
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                fecharModal();
            }
        });
    });
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
