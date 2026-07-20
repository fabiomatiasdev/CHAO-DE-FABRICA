<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="card-box" style="max-width: 800px; margin: 0 auto;">
    <div class="card-title-box">
        <h3>Preencha as Especificações do Modelo</h3>
    </div>

    <form action="<?= $action ?>" method="POST" enctype="multipart/form-data">
        <div class="form-row">
            <div class="form-group">
                <label for="nome" class="form-label">Nome do Modelo *</label>
                <input type="text" id="nome" name="nome" class="form-control" value="<?= htmlspecialchars($modelo['nome'] ?? '') ?>" placeholder="Ex: Camiseta Básica Masculina" required>
            </div>
            
            <div class="form-group">
                <label for="referencia" class="form-label">Referência / Código *</label>
                <input type="text" id="referencia" name="referencia" class="form-control" value="<?= htmlspecialchars($modelo['referencia'] ?? '') ?>" placeholder="Ex: CAM-001" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="categoria" class="form-label">Categoria *</label>
                <input type="text" id="categoria" name="categoria" class="form-control" value="<?= htmlspecialchars($modelo['categoria'] ?? '') ?>" placeholder="Ex: Camisetas, Calças, Vestidos" required>
            </div>

            <div class="form-group">
                <label for="status" class="form-label">Status do Modelo</label>
                <select id="status" name="status" class="form-control">
                    <option value="ativo" <?= ($modelo['status'] ?? 'ativo') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                    <option value="inativo" <?= ($modelo['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                </select>
            </div>
        </div>

        <input type="hidden" name="variantes_json" id="variantes_json">

        <div style="margin-top: 15px; margin-bottom: 20px; border: 1px solid var(--border); padding: 15px; border-radius: 8px; background-color: #f8fafc;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom: 1px solid var(--border); padding-bottom:8px;">
                <h4 style="margin:0; font-size:14px; font-weight:600; color:#334155;">Variantes (Cores e Tamanhos) *</h4>
                <button type="button" class="btn btn-secondary" id="btn-add-cor" style="padding: 6px 12px; font-size:12px;">
                    <i data-lucide="plus" style="width:14px; height:14px;"></i> Adicionar Cor
                </button>
            </div>

            <div id="variantes-container" style="display:flex; flex-direction:column; gap:15px;">
                <!-- Linhas de Cores e Tamanhos serão inseridas aqui dinamicamente pelo JS -->
            </div>
        </div>

        <div class="form-group" style="margin-top: 15px;">
            <label for="imagem" class="form-label">Imagem do Modelo (Opcional)</label>
            <input type="file" id="imagem" name="imagem" class="form-control" accept="image/*">
            <?php if (!empty($modelo['imagem'])): ?>
                <div style="margin-top: 10px; display: flex; align-items: center; gap: 10px;">
                    <img src="<?= htmlspecialchars($modelo['imagem']) ?>" alt="Atual" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border);">
                    <span style="font-size: 12px; color: var(--muted);">Imagem atual cadastrada. Se selecionar outra, a antiga será substituída.</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <a href="/produtos" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar Modelo</button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('variantes-container');
        const btnAddCor = document.getElementById('btn-add-cor');
        const inputVariantesJson = document.getElementById('variantes_json');
        const form = container.closest('form');

        // Dados iniciais injetados pelo PHP (para edição)
        const variantesIniciais = <?= isset($variantes) ? json_encode($variantes) : '[]' ?>;

        // Renderizar variantes iniciais se houver
        if (variantesIniciais.length > 0) {
            // Agrupar variantes por cor
            const coresMap = {};
            variantesIniciais.forEach(v => {
                if (!coresMap[v.cor]) {
                    coresMap[v.cor] = [];
                }
                coresMap[v.cor].push(v.tamanho);
            });

            for (const cor in coresMap) {
                adicionarBlocoCor(cor, coresMap[cor]);
            }
        } else {
            // Se for novo cadastro, adiciona um bloco vazio inicial
            adicionarBlocoCor('', []);
        }

        btnAddCor.addEventListener('click', function() {
            adicionarBlocoCor('', []);
        });

        function adicionarBlocoCor(corNome = '', tamanhosList = []) {
            const bloco = document.createElement('div');
            bloco.className = 'bloco-cor';
            bloco.style.display = 'grid';
            bloco.style.gridTemplateColumns = '200px 1fr 40px';
            bloco.style.gap = '15px';
            bloco.style.alignItems = 'start';
            bloco.style.border = '1px solid #e2e8f0';
            bloco.style.padding = '12px';
            bloco.style.borderRadius = '6px';
            bloco.style.backgroundColor = '#ffffff';

            bloco.innerHTML = `
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-size:11px;">Cor *</label>
                    <input type="text" class="form-control input-cor" value="${corNome}" placeholder="Ex: Azul Royal" required>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" style="font-size:11px;">Tamanhos *</label>
                    <div style="display:flex; gap:8px; margin-bottom:8px;">
                        <input type="text" class="form-control input-novo-tamanho" placeholder="Ex: M ou 40" style="padding: 4px 8px; font-size:12px; height: auto;">
                        <button type="button" class="btn btn-secondary btn-add-tamanho" style="padding: 4px 10px; font-size:12px; height: auto;">
                            +
                        </button>
                    </div>
                    <div class="tags-tamanhos-container" style="display:flex; flex-wrap:wrap; gap:5px;">
                        <!-- Tags de tamanhos inseridas aqui -->
                    </div>
                </div>
                <div style="display:flex; justify-content:center; align-items:center; height:100%; padding-top:20px;">
                    <button type="button" class="btn-remove-cor" style="border:none; background:none; color:var(--danger); cursor:pointer;">
                        <i data-lucide="trash-2" style="width:16px; height:16px;"></i>
                    </button>
                </div>
            `;

            container.appendChild(bloco);
            lucide.createIcons();

            const inputNovoTamanho = bloco.querySelector('.input-novo-tamanho');
            const btnAddTamanho = bloco.querySelector('.btn-add-tamanho');
            const tagsContainer = bloco.querySelector('.tags-tamanhos-container');
            const btnRemoveCor = bloco.querySelector('.btn-remove-cor');

            // Renderizar tamanhos iniciais
            tamanhosList.forEach(t => {
                adicionarTagTamanho(tagsContainer, t);
            });

            // Adicionar tamanho pelo botão
            btnAddTamanho.addEventListener('click', function() {
                const val = inputNovoTamanho.value.trim();
                if (val) {
                    adicionarTagTamanho(tagsContainer, val);
                    inputNovoTamanho.value = '';
                }
            });

            // Adicionar tamanho ao pressionar Enter
            inputNovoTamanho.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    btnAddTamanho.click();
                }
            });

            // Remover o bloco de cor
            btnRemoveCor.addEventListener('click', function() {
                const blocos = container.querySelectorAll('.bloco-cor');
                if (blocos.length > 1) {
                    bloco.remove();
                } else {
                    alert('O modelo precisa de pelo menos uma Cor.');
                }
            });
        }

        function adicionarTagTamanho(container, tamanho) {
            // Evitar duplicados no mesmo bloco
            const tagsExistentes = Array.from(container.querySelectorAll('.tag-tamanho-texto')).map(el => el.textContent);
            if (tagsExistentes.includes(tamanho)) return;

            const tag = document.createElement('span');
            tag.className = 'tag-tamanho';
            tag.style.display = 'inline-flex';
            tag.style.alignItems = 'center';
            tag.style.gap = '5px';
            tag.style.backgroundColor = '#e0f2fe';
            tag.style.color = '#0369a1';
            tag.style.padding = '3px 8px';
            tag.style.borderRadius = '12px';
            tag.style.fontSize = '12px';
            tag.style.fontWeight = '600';

            tag.innerHTML = `
                <span class="tag-tamanho-texto">${tamanho}</span>
                <span class="btn-remove-tag" style="cursor:pointer; color:#0284c7; font-weight:normal; font-size:10px;">&times;</span>
            `;

            tag.querySelector('.btn-remove-tag').addEventListener('click', function() {
                tag.remove();
            });

            container.appendChild(tag);
        }

        // Serializar JSON no submit
        form.addEventListener('submit', function(e) {
            const blocos = container.querySelectorAll('.bloco-cor');
            const dadosVariantes = [];
            let valid = true;

            blocos.forEach(bloco => {
                const cor = bloco.querySelector('.input-cor').value.trim();
                const tags = Array.from(bloco.querySelectorAll('.tag-tamanho-texto')).map(el => el.textContent);

                if (!cor) {
                    alert('Insira o nome de todas as cores cadastradas.');
                    valid = false;
                    return;
                }

                if (tags.length === 0) {
                    alert(`A cor "${cor}" precisa ter pelo menos um tamanho cadastrado.`);
                    valid = false;
                    return;
                }

                dadosVariantes.push({
                    cor: cor,
                    tamanhos: tags
                });
            });

            if (!valid) {
                e.preventDefault();
                return;
            }

            inputVariantesJson.value = JSON.stringify(dadosVariantes);
        });
    });
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
