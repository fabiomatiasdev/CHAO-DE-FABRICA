-- Schema do Banco de Dados para SQLite (PCP Confecção)

-- 1. Tenants (Empresas Clientes)
CREATE TABLE IF NOT EXISTS `tenants` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `nome` VARCHAR(255) NOT NULL,
  `cnpj` VARCHAR(20) NOT NULL UNIQUE,
  `plano` VARCHAR(100) NOT NULL DEFAULT 'Básico',
  `mensalidade` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `status_pagamento` VARCHAR(50) NOT NULL DEFAULT 'em dia',
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 2. Usuários
CREATE TABLE IF NOT EXISTS `users` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `tenant_id` INTEGER NOT NULL,
  `nome` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `senha` VARCHAR(255) NOT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'usuario',
  `status` VARCHAR(50) NOT NULL DEFAULT 'ativo',
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (`tenant_id`, `email`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
);

-- 3. Modelos de Produtos (Roupas)
CREATE TABLE IF NOT EXISTS `produtos_modelos` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `tenant_id` INTEGER NOT NULL,
  `nome` VARCHAR(255) NOT NULL,
  `referencia` VARCHAR(100) NOT NULL,
  `categoria` VARCHAR(100) NOT NULL,
  `imagem` VARCHAR(255) NULL,
  `grade_tamanhos` VARCHAR(100) NOT NULL DEFAULT 'P,M,G,GG',
  `cor` VARCHAR(50) NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'ativo',
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
);

-- 4. Matérias-Primas
CREATE TABLE IF NOT EXISTS `materias_primas` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `tenant_id` INTEGER NOT NULL,
  `nome` VARCHAR(255) NOT NULL,
  `unidade_medida` VARCHAR(20) NOT NULL DEFAULT 'M',
  `custo_unitario` DECIMAL(10, 4) NOT NULL DEFAULT 0.0000,
  `fornecedor` VARCHAR(255) NULL,
  `estoque_atual` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `estoque_minimo` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
);

-- 5. Fichas Técnicas
CREATE TABLE IF NOT EXISTS `fichas_tecnicas` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `tenant_id` INTEGER NOT NULL,
  `produto_modelo_id` INTEGER NOT NULL UNIQUE,
  `tempo_padrao` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `custo_mao_obra` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `folga_necessidades` DECIMAL(5, 2) NOT NULL DEFAULT 5.00,
  `folga_fadiga` DECIMAL(5, 2) NOT NULL DEFAULT 5.00,
  `folga_atrasos` DECIMAL(5, 2) NOT NULL DEFAULT 5.00,
  `folga_total` DECIMAL(5, 2) NOT NULL DEFAULT 15.00,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`produto_modelo_id`) REFERENCES `produtos_modelos` (`id`) ON DELETE CASCADE
);

-- 6. Itens da Ficha Técnica (Consumo)
CREATE TABLE IF NOT EXISTS `fichas_tecnicas_itens` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `tenant_id` INTEGER NOT NULL,
  `ficha_tecnica_id` INTEGER NOT NULL,
  `materia_prima_id` INTEGER NOT NULL,
  `quantidade` DECIMAL(10, 4) NOT NULL DEFAULT 0.0000,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`ficha_tecnica_id`) REFERENCES `fichas_tecnicas` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`materia_prima_id`) REFERENCES `materias_primas` (`id`) ON DELETE RESTRICT
);

-- 6b. Operações Cronometradas da Ficha Técnica
CREATE TABLE IF NOT EXISTS `fichas_tecnicas_operacoes` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `tenant_id` INTEGER NOT NULL,
  `ficha_tecnica_id` INTEGER NOT NULL,
  `operador` VARCHAR(255) NULL,
  `descricao_operacao` VARCHAR(255) NOT NULL,
  `tempo_1` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `tempo_2` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `tempo_3` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `media` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `observacoes` TEXT NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`ficha_tecnica_id`) REFERENCES `fichas_tecnicas` (`id`) ON DELETE CASCADE
);

-- 7. Oficinas / Facções
CREATE TABLE IF NOT EXISTS `oficinas_faccoes` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `tenant_id` INTEGER NOT NULL,
  `nome` VARCHAR(255) NOT NULL,
  `cnpj_cpf` VARCHAR(20) NOT NULL,
  `endereco` VARCHAR(255) NULL,
  `contato` VARCHAR(100) NULL,
  `capacidade_produtiva` INTEGER NOT NULL DEFAULT 0,
  `mao_obra_peca` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `status` VARCHAR(50) NOT NULL DEFAULT 'ativo',
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
);

-- 8. Pedidos de Venda
CREATE TABLE IF NOT EXISTS `pedidos_venda` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `tenant_id` INTEGER NOT NULL,
  `cliente` VARCHAR(255) NOT NULL,
  `produto_modelo_id` INTEGER NOT NULL,
  `quantidade` INTEGER NOT NULL DEFAULT 0,
  `tamanho` VARCHAR(20) NOT NULL,
  `prazo_entrega` DATE NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'pendente',
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`produto_modelo_id`) REFERENCES `produtos_modelos` (`id`) ON DELETE RESTRICT
);

-- 9. Custos Industriais (Mensais)
CREATE TABLE IF NOT EXISTS `custos_industriais` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `tenant_id` INTEGER NOT NULL,
  `descricao` VARCHAR(255) NOT NULL,
  `valor` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `tipo` VARCHAR(50) NOT NULL DEFAULT 'fixo',
  `mes_referencia` VARCHAR(7) NOT NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
);

-- 10. Ordens de Produção (OP)
CREATE TABLE IF NOT EXISTS `ordens_producao` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `tenant_id` INTEGER NOT NULL,
  `pedido_venda_id` INTEGER NULL,
  `produto_modelo_id` INTEGER NOT NULL,
  `oficina_faccao_id` INTEGER NULL,
  `quantidade` INTEGER NOT NULL DEFAULT 0,
  `prazo` DATE NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'aberta',
  `operadores` INTEGER NOT NULL DEFAULT 1,
  `estoque_baixado` TINYINT NOT NULL DEFAULT 0,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`pedido_venda_id`) REFERENCES `pedidos_venda` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`produto_modelo_id`) REFERENCES `produtos_modelos` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`oficina_faccao_id`) REFERENCES `oficinas_faccoes` (`id`) ON DELETE SET NULL
);

-- 11. Ordens de Corte
CREATE TABLE IF NOT EXISTS `ordens_corte` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `tenant_id` INTEGER NOT NULL,
  `ordem_producao_id` INTEGER NOT NULL,
  `tamanho` VARCHAR(20) NOT NULL,
  `quantidade_cortada` INTEGER NOT NULL DEFAULT 0,
  `responsavel` VARCHAR(100) NOT NULL,
  `data_corte` DATE NOT NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`ordem_producao_id`) REFERENCES `ordens_producao` (`id`) ON DELETE CASCADE
);

-- 12. Chão de Fábrica (Etapas)
CREATE TABLE IF NOT EXISTS `chao_fabrica_etapas` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `tenant_id` INTEGER NOT NULL,
  `ordem_producao_id` INTEGER NOT NULL,
  `etapa` VARCHAR(50) NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'pendente',
  `responsavel` VARCHAR(100) NULL,
  `atualizado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`ordem_producao_id`) REFERENCES `ordens_producao` (`id`) ON DELETE CASCADE
);

-- 13. Retornos de Facção
CREATE TABLE IF NOT EXISTS `retornos_faccao` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `tenant_id` INTEGER NOT NULL,
  `ordem_producao_id` INTEGER NOT NULL,
  `quantidade_enviada` INTEGER NOT NULL DEFAULT 0,
  `quantidade_retornada_boa` INTEGER NOT NULL DEFAULT 0,
  `quantidade_defeito_perda` INTEGER NOT NULL DEFAULT 0,
  `data_retorno` DATE NOT NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`ordem_producao_id`) REFERENCES `ordens_producao` (`id`) ON DELETE CASCADE
);

-- 14. Movimentações de Estoque
CREATE TABLE IF NOT EXISTS `estoque_movimentacoes` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `tenant_id` INTEGER NOT NULL,
  `tipo_item` VARCHAR(50) NOT NULL,
  `item_id` INTEGER NOT NULL,
  `quantidade` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `tipo_movimentacao` VARCHAR(50) NOT NULL,
  `motivo` VARCHAR(255) NOT NULL,
  `usuario_id` INTEGER NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
);

-- 15. Controle de Qualidade
CREATE TABLE IF NOT EXISTS `controle_qualidade` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `tenant_id` INTEGER NOT NULL,
  `ordem_producao_id` INTEGER NOT NULL,
  `quantidade_aprovada` INTEGER NOT NULL DEFAULT 0,
  `quantidade_reprovada` INTEGER NOT NULL DEFAULT 0,
  `tipo_defeito` VARCHAR(255) NULL,
  `responsavel` VARCHAR(100) NOT NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`ordem_producao_id`) REFERENCES `ordens_producao` (`id`) ON DELETE CASCADE
);

-- 16. Financeiro Facções
CREATE TABLE IF NOT EXISTS `financeiro_faccoes` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `tenant_id` INTEGER NOT NULL,
  `oficina_faccao_id` INTEGER NOT NULL,
  `retorno_faccao_id` INTEGER NULL,
  `valor_devido` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `valor_pago` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `status` VARCHAR(50) NOT NULL DEFAULT 'pendente',
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`oficina_faccao_id`) REFERENCES `oficinas_faccoes` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`retorno_faccao_id`) REFERENCES `retornos_faccao` (`id`) ON DELETE SET NULL
);

-- 17. Financeiro dos Clientes (Mensalidades SaaS)
CREATE TABLE IF NOT EXISTS `tenants_financeiro` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `tenant_id` INTEGER NOT NULL,
  `valor` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `mes_referencia` VARCHAR(7) NOT NULL,
  `status_pagamento` VARCHAR(50) NOT NULL DEFAULT 'pendente',
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
);

-- 18. Logs de Auditoria (Impersonation)
CREATE TABLE IF NOT EXISTS `logs_auditoria` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `superadmin_email` VARCHAR(255) NOT NULL,
  `tenant_id` INTEGER NOT NULL,
  `acao` VARCHAR(255) NOT NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
);

-- 19. Variantes de Cores e Tamanhos dos Modelos (Grade de Variantes)
CREATE TABLE IF NOT EXISTS `produtos_variantes` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `tenant_id` INTEGER NOT NULL,
  `produto_modelo_id` INTEGER NOT NULL,
  `cor` VARCHAR(50) NOT NULL,
  `tamanho` VARCHAR(20) NOT NULL,
  `estoque_atual` INTEGER NOT NULL DEFAULT 0,
  `estoque_minimo` INTEGER NOT NULL DEFAULT 0,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (`tenant_id`, `produto_modelo_id`, `cor`, `tamanho`),
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`produto_modelo_id`) REFERENCES `produtos_modelos` (`id`) ON DELETE CASCADE
);

