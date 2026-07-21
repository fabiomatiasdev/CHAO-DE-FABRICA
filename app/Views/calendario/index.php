<?php require __DIR__ . '/../layouts/header.php'; ?>

<?php
$nomesMeses = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];
$mesAnterior = $mes - 1;
$anoAnterior = $ano;
if ($mesAnterior < 1) { $mesAnterior = 12; $anoAnterior--; }

$mesProximo = $mes + 1;
$anoProximo = $ano;
if ($mesProximo > 12) { $mesProximo = 1; $anoProximo++; }
?>

<!-- Cabeçalho do Calendário & Ações -->
<div class="card-box" style="margin-bottom: 20px; padding: 18px 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <!-- Seletor de Mês e Ano -->
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="/calendario?mes=<?= $mesAnterior ?>&ano=<?= $anoAnterior ?>" class="btn btn-secondary" style="padding: 6px 12px;" title="Mês Anterior">
                <i data-lucide="chevron-left" style="width: 18px; height: 18px;"></i>
            </a>
            <h2 style="font-size: 20px; font-weight: 700; color: var(--foreground); margin: 0; min-width: 180px; text-align: center;">
                <?= $nomesMeses[$mes] ?> <?= $ano ?>
            </h2>
            <a href="/calendario?mes=<?= $mesProximo ?>&ano=<?= $anoProximo ?>" class="btn btn-secondary" style="padding: 6px 12px;" title="Próximo Mês">
                <i data-lucide="chevron-right" style="width: 18px; height: 18px;"></i>
            </a>
            <a href="/calendario" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">Hoje</a>
        </div>

        <!-- Botões de Ação -->
        <div style="display: flex; gap: 10px;">
            <button type="button" class="btn btn-primary" onclick="abrirModalColecao()">
                <i data-lucide="folder-plus" style="width: 16px; height: 16px;"></i> + Nova Coleção
            </button>
            <button type="button" class="btn btn-secondary" onclick="abrirModalTarefa()">
                <i data-lucide="check-square" style="width: 16px; height: 16px;"></i> + Nova Tarefa
            </button>
        </div>
    </div>
</div>

<!-- PAINEL DE DIAGNÓSTICO MRP E SIMULAÇÃO DA COLEÇÃO SELECIONADA -->
<?php if ($colecaoSelecionada && $simulacao): ?>
    <div class="card-box" style="margin-bottom: 25px; border-left: 5px solid <?= $simulacao['estoque_ok'] ? 'var(--success)' : 'var(--danger)' ?>;">
        <div class="card-title-box">
            <div>
                <span class="tenant-badge" style="background: #f1f5f9; color: #475569; border-color: #cbd5e1;">Simulação MRP de Insumos</span>
                <h3 style="margin-top: 5px;">Coleção: <strong><?= htmlspecialchars($colecaoSelecionada['nome']) ?></strong></h3>
                <div style="font-size: 13px; color: var(--muted); margin-top: 2px;">
                    Período: <?= date('d/m/Y', strtotime($colecaoSelecionada['data_inicio'])) ?> a <?= date('d/m/Y', strtotime($colecaoSelecionada['data_fim'])) ?> | 
                    Total: <strong><?= $simulacao['total_ops'] ?> OP(s)</strong> (<?= number_format($simulacao['total_pecas'], 0, ',', '.') ?> peças no total)
                </div>
            </div>
            <a href="/calendario" class="btn btn-secondary" style="font-size: 12px;">Fechar Análise</a>
        </div>

        <!-- Status do Alerta de Estoque -->
        <div style="padding: 15px; border-radius: 8px; margin-bottom: 20px; background: <?= $simulacao['estoque_ok'] ? '#f0fdf4' : '#fef2f2' ?>; border: 1px solid <?= $simulacao['estoque_ok'] ? '#bbf7d0' : '#fecaca' ?>; display: flex; align-items: center; gap: 15px;">
            <?php if ($simulacao['estoque_ok']): ?>
                <div style="width: 40px; height: 40px; border-radius: 50%; background: #22c55e; color: white; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i data-lucide="check-circle-2" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <strong style="color: #15803d; font-size: 15px; display: block;">Estoque Totalmente Disponível para esta Coleção!</strong>
                    <span style="font-size: 13px; color: #166534;">Todos os insumos e matérias-primas necessários para produzir esta coleção estão disponíveis no estoque.</span>
                </div>
            <?php else: ?>
                <div style="width: 40px; height: 40px; border-radius: 50%; background: #ef4444; color: white; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i data-lucide="alert-triangle" style="width: 24px; height: 24px;"></i>
                </div>
                <div>
                    <strong style="color: #b91c1c; font-size: 15px; display: block;">Atenção: Falta de Insumos Faltantes no Estoque!</strong>
                    <span style="font-size: 13px; color: #991b1b;">Há <strong><?= $simulacao['insumos_faltantes'] ?> matéria(s)-prima(s) insuficientes</strong> no estoque para concluir a produção estimada. Veja a lista abaixo para providenciar compras.</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Indicadores Orçamentários -->
        <div class="dashboard-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 20px;">
            <div class="stat-card stat-info">
                <span class="stat-title">Custo Estimado Matéria-Prima (BOM)</span>
                <span class="stat-value">R$ <?= number_format($simulacao['custo_mp_total'], 2, ',', '.') ?></span>
            </div>
            <div class="stat-card stat-warning">
                <span class="stat-title">Custo Estimado Mão de Obra (Oficinas)</span>
                <span class="stat-value">R$ <?= number_format($simulacao['custo_mo_total'], 2, ',', '.') ?></span>
            </div>
            <div class="stat-card stat-primary">
                <span class="stat-title">Investimento Fabril Total Estimado</span>
                <span class="stat-value" style="color: var(--primary);">R$ <?= number_format($simulacao['custo_fabril_total'], 2, ',', '.') ?></span>
            </div>
        </div>

        <!-- Tabela de Insumos com Alertas -->
        <h4 style="font-size: 14px; font-weight: 700; color: #334155; margin-bottom: 12px;">Explosão de Materiais / Insumos Necessários</h4>
        <div class="table-responsive">
            <table class="table-custom" style="min-width: 650px;">
                <thead>
                    <tr>
                        <th>Insumo / Matéria-Prima</th>
                        <th>Estoque Atual</th>
                        <th>Necessidade do Lote</th>
                        <th>Status / Falta</th>
                        <th>Custo Estimado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($simulacao['insumos'])): ?>
                        <tr><td colspan="5" class="empty-state">Nenhum insumo mapeado nas Fichas Técnicas das OPs desta coleção.</td></tr>
                    <?php else: ?>
                        <?php foreach ($simulacao['insumos'] as $ins): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($ins['nome']) ?></strong></td>
                                <td><?= number_format($ins['estoque_atual'], 2, ',', '.') ?> <?= htmlspecialchars($ins['unidade_medida']) ?></td>
                                <td><strong><?= number_format($ins['qtd_necessaria'], 2, ',', '.') ?> <?= htmlspecialchars($ins['unidade_medida']) ?></strong></td>
                                <td>
                                    <?php if ($ins['suficiente']): ?>
                                        <span class="tenant-badge" style="background:#dcfce7; color:#15803d; border-color:#bbf7d0;">🟢 Estoque OK</span>
                                    <?php else: ?>
                                        <span class="tenant-badge" style="background:#fee2e2; color:#b91c1c; border-color:#fecaca;">🔴 Faltam <?= number_format($ins['qtd_faltante'], 2, ',', '.') ?> <?= htmlspecialchars($ins['unidade_medida']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>R$ <?= number_format($ins['custo_total'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- CALENDÁRIO MENSAL GRID -->
<div class="card-box" style="padding: 0; overflow: hidden;">
    <div style="display: grid; grid-template-columns: repeat(7, 1fr); background: #f8fafc; border-bottom: 1px solid var(--border); font-weight: 700; text-align: center; font-size: 13px; color: #475569;">
        <div style="padding: 12px;">Dom</div>
        <div style="padding: 12px;">Seg</div>
        <div style="padding: 12px;">Ter</div>
        <div style="padding: 12px;">Qua</div>
        <div style="padding: 12px;">Qui</div>
        <div style="padding: 12px;">Sex</div>
        <div style="padding: 12px;">Sáb</div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(7, 1fr); auto-rows: minmax(110px, auto); background: var(--border); gap: 1px;">
        <?php
        // Células vazias do início do mês
        for ($i = 0; $i < $diaSemanaInicio; $i++):
        ?>
            <div style="background: #f1f5f9; padding: 8px;"></div>
        <?php endfor; ?>

        <?php
        $hojeStr = date('Y-m-d');
        for ($dia = 1; $dia <= $diasNoMes; $dia++):
            $dataStr = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
            $isHoje = ($dataStr === $hojeStr);

            // Filtrar itens do dia
            $colecoesDoDia = array_filter($colecoes, function($c) use ($dataStr) {
                return $dataStr >= $c['data_inicio'] && $dataStr <= $c['data_fim'];
            });

            $opsDoDia = array_filter($opsNoMes, function($op) use ($dataStr) {
                return $op['prazo'] === $dataStr;
            });

            $tarefasDoDia = array_filter($tarefasNoMes, function($t) use ($dataStr) {
                return $t['data_execucao'] === $dataStr;
            });
        ?>
            <div style="background: white; padding: 8px; min-height: 110px; display: flex; flex-direction: column; gap: 4px; <?= $isHoje ? 'background: #eff6ff;' : '' ?>">
                <div style="font-size: 12px; font-weight: 700; color: <?= $isHoje ? 'var(--primary)' : '#64748b' ?>; display: flex; justify-content: space-between;">
                    <span><?= $dia ?></span>
                    <?php if ($isHoje): ?>
                        <span style="font-size: 10px; background: var(--primary); color: white; padding: 1px 5px; border-radius: 4px;">HOJE</span>
                    <?php endif; ?>
                </div>

                <!-- Eventos Coleção -->
                <?php foreach ($colecoesDoDia as $c): ?>
                    <a href="/calendario?mes=<?= $mes ?>&ano=<?= $ano ?>&colecao_id=<?= $c['id'] ?>" 
                       style="background: #8b5cf6; color: white; padding: 3px 6px; border-radius: 4px; font-size: 11px; text-decoration: none; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" 
                       title="Coleção: <?= htmlspecialchars($c['nome']) ?> (Clique para simular MRP)">
                        🟣 <strong><?= htmlspecialchars($c['nome']) ?></strong>
                    </a>
                <?php endforeach; ?>

                <!-- Eventos OPs -->
                <?php foreach ($opsDoDia as $op): ?>
                    <div onclick='verDetalhesOP(<?= json_encode($op) ?>)' 
                         style="background: #0284c7; color: white; padding: 3px 6px; border-radius: 4px; font-size: 10px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer;"
                         title="Clique para ver detalhes da OP #<?= $op['id'] ?>">
                        🔵 OP #<?= $op['id'] ?> - <?= htmlspecialchars($op['referencia']) ?>
                    </div>
                <?php endforeach; ?>

                <!-- Eventos Tarefas -->
                <?php foreach ($tarefasDoDia as $t): ?>
                    <div onclick='verDetalhesTarefa(<?= json_encode($t) ?>)' 
                         style="background: <?= $t['status'] === 'concluido' ? '#16a34a' : '#f59e0b' ?>; color: white; padding: 3px 6px; border-radius: 4px; font-size: 10px; display: flex; justify-content: space-between; align-items: center; cursor: pointer;"
                         title="Clique para ver ou alternar status da tarefa">
                        <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; <?= $t['status'] === 'concluido' ? 'text-decoration: line-through;' : '' ?>">
                            <?= $t['status'] === 'concluido' ? '✓' : '📌' ?> <?= htmlspecialchars($t['titulo']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endfor; ?>
    </div>
</div>

<!-- LISTA DE COLEÇÕES CADASTRADAS -->
<div class="card-box" style="margin-top: 25px;">
    <div class="card-title-box">
        <h3>Coleções Agendadas</h3>
    </div>
    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Coleção</th>
                    <th>Período</th>
                    <th>OPs Vinculadas</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($colecoes)): ?>
                    <tr><td colspan="5" class="empty-state">Nenhuma coleção cadastrada no momento.</td></tr>
                <?php else: ?>
                    <?php foreach ($colecoes as $c): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($c['nome']) ?></strong><br><small style="color:var(--muted);"><?= htmlspecialchars($c['descricao'] ?: 'Sem descrição') ?></small></td>
                            <td><?= date('d/m/Y', strtotime($c['data_inicio'])) ?> a <?= date('d/m/Y', strtotime($c['data_fim'])) ?></td>
                            <td><strong><?= $c['total_ops'] ?> OP(s)</strong></td>
                            <td><span class="tenant-badge"><?= ucfirst($c['status']) ?></span></td>
                            <td>
                                <a href="/calendario?mes=<?= $mes ?>&ano=<?= $ano ?>&colecao_id=<?= $c['id'] ?>" class="btn btn-secondary" style="padding: 4px 8px; font-size: 12px;">⚡ Simular MRP</a>
                                <a href="/colecoes/excluir?id=<?= $c['id'] ?>" class="btn btn-secondary" style="padding: 4px 8px; font-size: 12px; color: var(--danger);" onclick="return confirm('Deseja excluir esta coleção?');">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL CADASTRAR COLEÇÃO -->
<div id="modalColecao" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; justify-content: center; align-items: center; padding: 20px;">
    <div style="background: white; border-radius: 8px; max-width: 600px; width: 100%; padding: 24px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;">Nova Coleção / Lançamento</h3>
            <button type="button" onclick="fecharModalColecao()" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>

        <form action="/colecoes/novo" method="POST">
            <div class="form-group">
                <label class="form-label">Nome da Coleção *</label>
                <input type="text" name="nome" class="form-control" placeholder="Ex: Primavera / Verão 2026" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label">Data Início *</label>
                    <input type="date" name="data_inicio" class="form-control" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Data Fim (Lançamento/Entrega) *</label>
                    <input type="date" name="data_fim" class="form-control" required value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Descrição / Observações</label>
                <textarea name="descricao" class="form-control" rows="2" placeholder="Detalhes sobre a coleção..."></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Vincular Ordens de Produção (OPs) nesta Coleção</label>
                <div style="max-height: 150px; overflow-y: auto; border: 1px solid var(--border); border-radius: 6px; padding: 10px;">
                    <?php if (empty($todasOps)): ?>
                        <span style="font-size: 12px; color: var(--muted);">Nenhuma OP disponível para vincular.</span>
                    <?php else: ?>
                        <?php foreach ($todasOps as $op): ?>
                            <label style="display: block; font-size: 13px; margin-bottom: 6px; cursor: pointer;">
                                <input type="checkbox" name="ops[]" value="<?= $op['id'] ?>">
                                <strong>OP #<?= $op['id'] ?></strong> — <?= htmlspecialchars($op['referencia']) ?> - <?= htmlspecialchars($op['modelo_nome']) ?> (<?= $op['quantidade'] ?> pçs)
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" onclick="fecharModalColecao()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Coleção</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL CADASTRAR TAREFA -->
<div id="modalTarefa" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; justify-content: center; align-items: center; padding: 20px;">
    <div style="background: white; border-radius: 8px; max-width: 500px; width: 100%; padding: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;">Nova Tarefa / Afazer de Produção</h3>
            <button type="button" onclick="fecharModalTarefa()" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>

        <form action="/tarefas/novo" method="POST">
            <div class="form-group">
                <label class="form-label">Título da Tarefa *</label>
                <input type="text" name="titulo" class="form-control" placeholder="Ex: Comprar zíperes para coleção" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label">Data de Execução *</label>
                    <input type="date" name="data_execucao" class="form-control" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Responsável</label>
                    <input type="text" name="responsavel" class="form-control" placeholder="Ex: João Compras">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Descrição</label>
                <textarea name="descricao" class="form-control" rows="2" placeholder="Detalhes da tarefa..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" onclick="fecharModalTarefa()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Agendar Tarefa</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL VER DETALHES TAREFA -->
<div id="modalDetalhesTarefa" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; justify-content: center; align-items: center; padding: 20px;">
    <div style="background: white; border-radius: 8px; max-width: 500px; width: 100%; padding: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0;" id="detalheTarefaTitulo">Detalhes da Tarefa</h3>
            <button type="button" onclick="fecharModalDetalhesTarefa()" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>

        <div style="display: flex; flex-direction: column; gap: 12px; font-size: 14px; color: #334155;">
            <div>Status: <span id="detalheTarefaStatusBadge"></span></div>
            <div>Data de Execução: <strong id="detalheTarefaData"></strong></div>
            <div>Responsável: <strong id="detalheTarefaResponsavel"></strong></div>
            <div style="border-top: 1px solid var(--border); padding-top: 10px;">
                <span style="font-size: 12px; color: var(--muted); font-weight: 700; text-transform: uppercase;">Descrição</span>
                <p id="detalheTarefaDescricao" style="margin-top: 4px; color: #475569; background: #f8fafc; padding: 10px; border-radius: 6px; border: 1px solid #e2e8f0;"></p>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; gap: 10px; flex-wrap: wrap;">
            <a id="btnAlternarStatusTarefa" href="#" class="btn btn-primary" style="font-size: 13px;"></a>
            <div style="display: flex; gap: 8px;">
                <a id="btnExcluirTarefa" href="#" class="btn btn-secondary" style="color: var(--danger); font-size: 13px;" onclick="return confirm('Deseja excluir esta tarefa?');">Excluir</a>
                <button type="button" class="btn btn-secondary" onclick="fecharModalDetalhesTarefa()">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL VER DETALHES OP -->
<div id="modalDetalhesOP" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; justify-content: center; align-items: center; padding: 20px;">
    <div style="background: white; border-radius: 8px; max-width: 500px; width: 100%; padding: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0;" id="detalheOPTitulo">Detalhes da Ordem de Produção</h3>
            <button type="button" onclick="fecharModalDetalhesOP()" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>

        <div style="display: flex; flex-direction: column; gap: 10px; font-size: 14px; color: #334155;">
            <div>Modelo / Referência: <strong id="detalheOPModelo" style="color: var(--primary);"></strong></div>
            <div>Quantidade de Peças: <strong id="detalheOPQtd"></strong></div>
            <div>Prazo de Entrega: <strong id="detalheOPPrazo"></strong></div>
            <div>Status Atual: <span id="detalheOPStatusBadge" class="tenant-badge"></span></div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px;">
            <a id="btnDiagnosticoOP" href="#" class="btn btn-secondary" style="font-size: 13px;">⚡ Diagnóstico OP</a>
            <a id="btnEditarOP" href="#" class="btn btn-primary" style="font-size: 13px;">Editar OP</a>
            <button type="button" class="btn btn-secondary" onclick="fecharModalDetalhesOP()">Fechar</button>
        </div>
    </div>
</div>

<script>
    function abrirModalColecao() {
        document.getElementById('modalColecao').style.display = 'flex';
    }
    function fecharModalColecao() {
        document.getElementById('modalColecao').style.display = 'none';
    }
    function abrirModalTarefa() {
        document.getElementById('modalTarefa').style.display = 'flex';
    }
    function fecharModalTarefa() {
        document.getElementById('modalTarefa').style.display = 'none';
    }

    function verDetalhesTarefa(t) {
        document.getElementById('detalheTarefaTitulo').innerText = t.titulo;
        document.getElementById('detalheTarefaData').innerText = t.data_execucao ? t.data_execucao.split('-').reverse().join('/') : '-';
        document.getElementById('detalheTarefaResponsavel').innerText = t.responsavel || 'Não atribuído';
        document.getElementById('detalheTarefaDescricao').innerText = t.descricao || 'Sem descrição cadastrada.';
        
        const badge = document.getElementById('detalheTarefaStatusBadge');
        const btnAlternar = document.getElementById('btnAlternarStatusTarefa');

        if (t.status === 'concluido') {
            badge.innerHTML = '<span class="tenant-badge" style="background:#dcfce7; color:#15803d; border-color:#bbf7d0;">✓ Concluída</span>';
            btnAlternar.innerText = '↺ Marcar como Não Feito (Pendente)';
            btnAlternar.className = 'btn btn-secondary';
        } else {
            badge.innerHTML = '<span class="tenant-badge" style="background:#fef3c7; color:#b45309; border-color:#fde68a;">📌 Pendente</span>';
            btnAlternar.innerText = '✓ Marcar como Feito (Concluído)';
            btnAlternar.className = 'btn btn-primary';
        }

        btnAlternar.href = '/tarefas/alternar?id=' + t.id;
        document.getElementById('btnExcluirTarefa').href = '/tarefas/excluir?id=' + t.id;
        
        document.getElementById('modalDetalhesTarefa').style.display = 'flex';
    }

    function fecharModalDetalhesTarefa() {
        document.getElementById('modalDetalhesTarefa').style.display = 'none';
    }

    function verDetalhesOP(op) {
        document.getElementById('detalheOPTitulo').innerText = 'OP #' + op.id;
        document.getElementById('detalheOPModelo').innerText = op.referencia + ' - ' + op.modelo_nome;
        document.getElementById('detalheOPQtd').innerText = parseInt(op.quantidade).toLocaleString('pt-BR') + ' pçs';
        document.getElementById('detalheOPPrazo').innerText = op.prazo ? op.prazo.split('-').reverse().join('/') : '-';
        document.getElementById('detalheOPStatusBadge').innerText = op.status;
        
        document.getElementById('btnDiagnosticoOP').href = '/relatorios/diagnostico-op?op_id=' + op.id;
        document.getElementById('btnEditarOP').href = '/ops/editar?id=' + op.id;

        document.getElementById('modalDetalhesOP').style.display = 'flex';
    }

    function fecharModalDetalhesOP() {
        document.getElementById('modalDetalhesOP').style.display = 'none';
    }
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
