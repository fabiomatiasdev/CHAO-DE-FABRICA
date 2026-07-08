<?php require __DIR__ . '/../layouts/header.php'; ?>

<div class="card-box" style="max-width: 800px; margin: 0 auto;">
    <div class="card-title-box">
        <h3>Preencha as Informações da Oficina</h3>
    </div>

    <form action="<?= $action ?>" method="POST">
        <div class="form-group">
            <label for="nome" class="form-label">Nome da Oficina / Facção *</label>
            <input type="text" id="nome" name="nome" class="form-control" value="<?= htmlspecialchars($oficina['nome'] ?? '') ?>" placeholder="Ex: Facção Costura Fina" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="cnpj_cpf" class="form-label">CNPJ ou CPF *</label>
                <input type="text" id="cnpj_cpf" name="cnpj_cpf" class="form-control" value="<?= htmlspecialchars($oficina['cnpj_cpf'] ?? '') ?>" placeholder="Ex: 00.000.000/0001-00 ou 000.000.000-00" required>
            </div>

            <div class="form-group">
                <label for="contato" class="form-label">Contato (Nome / Telefone)</label>
                <input type="text" id="contato" name="contato" class="form-control" value="<?= htmlspecialchars($oficina['contato'] ?? '') ?>" placeholder="Ex: Maria José (11) 99999-8888">
            </div>
        </div>

        <div class="form-group">
            <label for="endereco" class="form-label">Endereço Completo</label>
            <input type="text" id="endereco" name="endereco" class="form-control" value="<?= htmlspecialchars($oficina['endereco'] ?? '') ?>" placeholder="Ex: Rua das Rendas, 123 - Centro">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="capacidade_produtiva" class="form-label">Capacidade Produtiva Estimada (Peças/Mês) *</label>
                <input type="number" id="capacidade_produtiva" name="capacidade_produtiva" class="form-control" value="<?= htmlspecialchars($oficina['capacidade_produtiva'] ?? '0') ?>" min="0" required>
            </div>

            <div class="form-group">
                <label for="mao_obra_peca" class="form-label">Custo Mão de Obra por Peça Costurada (R$) *</label>
                <input type="number" id="mao_obra_peca" name="mao_obra_peca" class="form-control" step="0.01" value="<?= htmlspecialchars($oficina['mao_obra_peca'] ?? '0.00') ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="status" class="form-label">Status do Cadastro</label>
            <select id="status" name="status" class="form-control">
                <option value="ativo" <?= ($oficina['status'] ?? 'ativo') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                <option value="inativo" <?= ($oficina['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
            </select>
        </div>

        <div class="form-actions">
            <a href="/oficinas" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar Oficina</button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../layouts/header.php'; ?>
