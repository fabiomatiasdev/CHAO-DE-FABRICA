<?php require __DIR__ . '/../layouts/header.php'; ?>

<!-- Painel de Ações / Apontamento Rápido -->
<div class="card-box" style="margin-bottom: 25px;">
    <div class="card-title-box">
        <h3>Apontar Etapa de Produção (Apontamento Chão de Fábrica)</h3>
    </div>
    
    <form action="/chao-fabrica/apontar" method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: flex-end;">
        <div class="form-group" style="margin-bottom:0;">
            <label for="ordem_producao_id" class="form-label">Ordem de Produção (OP)</label>
            <select id="ordem_producao_id" name="ordem_producao_id" class="form-control" required>
                <option value="">-- Selecione a OP --</option>
                <?php foreach ($ops as $op): ?>
                    <?php if ($op['op_status'] !== 'concluída'): ?>
                        <option value="<?= $op['id'] ?>">OP #<?= $op['id'] ?> — <?= htmlspecialchars($op['referencia']) ?> (<?= $op['quantidade'] ?> pçs)</option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin-bottom:0;">
            <label for="etapa" class="form-label">Etapa Produtiva</label>
            <select id="etapa" name="etapa" class="form-control" required>
                <option value="">-- Selecione a Etapa --</option>
                <option value="corte">1. Corte</option>
                <option value="costura">2. Costura</option>
                <option value="acabamento">3. Acabamento</option>
                <option value="revisão">4. Revisão (Qualidade)</option>
                <option value="embalagem">5. Embalagem</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom:0;">
            <label for="status" class="form-label">Novo Status</label>
            <select id="status" name="status" class="form-control" required>
                <option value="em andamento">Em Andamento</option>
                <option value="conclúido">Concluído</option>
                <option value="pendente">Pendente / Pausado</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom:0;">
            <label for="responsavel" class="form-label">Responsável / Operador</label>
            <input type="text" id="responsavel" name="responsavel" class="form-control" placeholder="Nome do operador" required>
        </div>

        <button type="submit" class="btn btn-primary"><i data-lucide="play" style="width:16px;height:16px;"></i> Gravar Apontamento</button>
    </form>
</div>

<!-- Grid Visual do Progresso das OPs -->
<div class="card-box">
    <div class="card-title-box">
        <h3>Quadro de Acompanhamento Produtivo</h3>
    </div>

    <?php if (empty($ops)): ?>
        <div class="empty-state">
            <i data-lucide="activity" style="width:32px;height:32px;"></i>
            <p>Nenhuma ordem de produção em andamento.</p>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <?php foreach ($ops as $op): ?>
                <div style="border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; background-color: white;">
                    <!-- Cabeçalho da OP no Quadro -->
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px; border-bottom:1px solid #f1f5f9; padding-bottom:10px;">
                        <div>
                            <span style="font-size:12px; font-weight:700; color:var(--muted);">OPERAÇÃO</span>
                            <h4 style="font-size:16px; font-weight:700; color:var(--foreground); display:inline-block; margin-left: 8px;">
                                OP #<?= $op['id'] ?> — [<?= htmlspecialchars($op['referencia']) ?>] <?= htmlspecialchars($op['modelo_nome']) ?>
                            </h4>
                        </div>
                        <div style="display:flex; gap:15px; align-items:center;">
                            <span>Meta: <strong><?= number_format($op['quantidade'], 0, ',', '.') ?> pçs</strong></span>
                            <?php if ($op['op_status'] === 'concluída'): ?>
                                <span class="badge badge-success">Concluída</span>
                            <?php else: ?>
                                <span class="badge badge-info">Em Produção</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Linha do Tempo / Etapas Produtivas -->
                    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px;">
                        <?php 
                        $listaEtapas = ['corte', 'costura', 'acabamento', 'revisão', 'embalagem'];
                        $etapaLabels = [
                            'corte' => '1. Corte',
                            'costura' => '2. Costura',
                            'acabamento' => '3. Acabamento',
                            'revisão' => '4. Revisão',
                            'embalagem' => '5. Embalagem'
                        ];
                        $etapaIcones = [
                            'corte' => 'slice',
                            'costura' => 'pocket',
                            'acabamento' => 'sparkles',
                            'revisão' => 'check-square',
                            'embalagem' => 'package'
                        ];
                        
                        foreach ($listaEtapas as $etNome):
                            $etDado = $op['etapas'][$etNome] ?? ['status' => 'pendente', 'responsavel' => ''];
                            $etStatus = $etDado['status'];
                            $etResp = $etDado['responsavel'];
                            
                            // Cores do Card
                            $bgColor = '#f8fafc';
                            $borderColor = '#e2e8f0';
                            $textColor = '#64748b';
                            $badgeClass = 'badge-secondary';
                            
                            if ($etStatus === 'em andamento') {
                                $bgColor = '#eff6ff';
                                $borderColor = '#bfdbfe';
                                $textColor = '#1d4ed8';
                                $badgeClass = 'badge-info';
                            } elseif ($etStatus === 'conclúido') {
                                $bgColor = '#f0fdf4';
                                $borderColor = '#bbf7d0';
                                $textColor = '#166534';
                                $badgeClass = 'badge-success';
                            }
                        ?>
                            <div style="background-color: <?= $bgColor ?>; border: 1px solid <?= $borderColor ?>; padding: 12px; border-radius: 8px; position: relative;">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                    <span style="font-size:13px; font-weight:600; color: <?= $textColor ?>;"><?= $etapaLabels[$etNome] ?></span>
                                    <span class="badge <?= $badgeClass ?>" style="font-size:9px; padding: 2px 5px;"><?= $etStatus ?></span>
                                </div>
                                
                                <div style="font-size:11px; color: var(--muted); min-height: 25px;">
                                    <?php if ($etStatus === 'conclúido'): ?>
                                        <i data-lucide="check" style="width:12px;height:12px;color:var(--success);display:inline-block;vertical-align:middle;margin-right:2px;"></i>
                                        <span>Resp: <?= htmlspecialchars($etResp ?: 'Oficina') ?></span>
                                    <?php elseif ($etStatus === 'em andamento'): ?>
                                        <span class="pulse-icon" style="display:inline-block; width:6px; height:6px; background-color:var(--primary); border-radius:50%; margin-right:4px;"></span>
                                        <span>Ativo: <?= htmlspecialchars($etResp ?: 'Aguardando') ?></span>
                                    <?php else: ?>
                                        <span>Aguardando etapa anterior</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
