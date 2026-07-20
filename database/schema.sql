-- Schema do Banco de Dados para o Sistema de PCP Multi-tenant
-- ATENÇÃO: Crie o banco de dados pelo painel da hospedagem antes de importar este arquivo.

-- 1. Tenants (Empresas Clientes)
CREATE TABLE IF NOT EXISTS `tenants` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(255) NOT NULL,
  `cnpj` VARCHAR(20) NOT NULL UNIQUE,
  `plano` VARCHAR(100) NOT NULL DEFAULT 'Básico',
  `mensalidade` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `status_pagamento` ENUM('em dia', 'atrasado', 'cancelado') NOT NULL DEFAULT 'em dia',
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Usuários
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `nome` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `senha` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'usuario') NOT NULL DEFAULT 'usuario',
  `status` ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_tenant_email` (`tenant_id`, `email`),
  CONSTRAINT `fk_users_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Modelos de Produtos (Roupas)
CREATE TABLE IF NOT EXISTS `produtos_modelos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `nome` VARCHAR(255) NOT NULL,
  `referencia` VARCHAR(100) NOT NULL,
  `categoria` VARCHAR(100) NOT NULL,
  `imagem` VARCHAR(255) NULL,
  `grade_tamanhos` VARCHAR(100) NOT NULL DEFAULT 'P,M,G,GG',
  `cor` VARCHAR(50) NOT NULL,
  `status` ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_produtos_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  INDEX `idx_produtos_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Matérias-Primas
CREATE TABLE IF NOT EXISTS `materias_primas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `nome` VARCHAR(255) NOT NULL,
  `unidade_medida` VARCHAR(20) NOT NULL DEFAULT 'M',
  `custo_unitario` DECIMAL(10, 4) NOT NULL DEFAULT 0.0000,
  `fornecedor` VARCHAR(255) NULL,
  `estoque_atual` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `estoque_minimo` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_materias_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  INDEX `idx_materias_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Fichas Técnicas
CREATE TABLE IF NOT EXISTS `fichas_tecnicas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `produto_modelo_id` INT NOT NULL UNIQUE,
  `tempo_padrao` DECIMAL(10, 2) NOT NULL DEFAULT 0.00, -- Tempo padrão de produção em minutos
  `custo_mao_obra` DECIMAL(10, 2) NOT NULL DEFAULT 0.00, -- Custo estimado de mão de obra direta
  `folga_necessidades` DECIMAL(5, 2) NOT NULL DEFAULT 5.00,
  `folga_fadiga` DECIMAL(5, 2) NOT NULL DEFAULT 5.00,
  `folga_atrasos` DECIMAL(5, 2) NOT NULL DEFAULT 5.00,
  `folga_total` DECIMAL(5, 2) NOT NULL DEFAULT 15.00,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_fichas_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fichas_produto` FOREIGN KEY (`produto_modelo_id`) REFERENCES `produtos_modelos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Itens da Ficha Técnica (Consumo)
CREATE TABLE IF NOT EXISTS `fichas_tecnicas_itens` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `ficha_tecnica_id` INT NOT NULL,
  `materia_prima_id` INT NOT NULL,
  `quantidade` DECIMAL(10, 4) NOT NULL DEFAULT 0.0000,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_fichas_itens_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fichas_itens_ficha` FOREIGN KEY (`ficha_tecnica_id`) REFERENCES `fichas_tecnicas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fichas_itens_materia` FOREIGN KEY (`materia_prima_id`) REFERENCES `materias_primas` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6b. Operações Cronometradas da Ficha Técnica
CREATE TABLE IF NOT EXISTS `fichas_tecnicas_operacoes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `ficha_tecnica_id` INT NOT NULL,
  `operador` VARCHAR(255) NULL,
  `descricao_operacao` VARCHAR(255) NOT NULL,
  `tempo_1` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `tempo_2` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `tempo_3` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `media` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `observacoes` TEXT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_fichas_ops_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fichas_ops_ficha` FOREIGN KEY (`ficha_tecnica_id`) REFERENCES `fichas_tecnicas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Oficinas / Facções
CREATE TABLE IF NOT EXISTS `oficinas_faccoes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `nome` VARCHAR(255) NOT NULL,
  `cnpj_cpf` VARCHAR(20) NOT NULL,
  `endereco` VARCHAR(255) NULL,
  `contato` VARCHAR(100) NULL,
  `capacidade_produtiva` INT NOT NULL DEFAULT 0, -- Peças/mês
  `mao_obra_peca` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `status` ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_oficinas_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  INDEX `idx_oficinas_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Pedidos de Venda
CREATE TABLE IF NOT EXISTS `pedidos_venda` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `cliente` VARCHAR(255) NOT NULL,
  `produto_modelo_id` INT NOT NULL,
  `quantidade` INT NOT NULL DEFAULT 0,
  `tamanho` VARCHAR(20) NOT NULL,
  `prazo_entrega` DATE NOT NULL,
  `status` ENUM('pendente', 'em produção', 'entregue') NOT NULL DEFAULT 'pendente',
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_pedidos_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pedidos_produto` FOREIGN KEY (`produto_modelo_id`) REFERENCES `produtos_modelos` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Custos Industriais (Mensais)
CREATE TABLE IF NOT EXISTS `custos_industriais` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `descricao` VARCHAR(255) NOT NULL,
  `valor` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `tipo` ENUM('fixo', 'indireto') NOT NULL DEFAULT 'fixo',
  `mes_referencia` VARCHAR(7) NOT NULL, -- formato YYYY-MM
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_custos_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Ordens de Produção (OP)
CREATE TABLE IF NOT EXISTS `ordens_producao` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `pedido_venda_id` INT NULL,
  `produto_modelo_id` INT NOT NULL,
  `oficina_faccao_id` INT NULL,
  `quantidade` INT NOT NULL DEFAULT 0,
  `prazo` DATE NOT NULL,
  `status` ENUM('aberta', 'em andamento', 'concluída', 'cancelada') NOT NULL DEFAULT 'aberta',
  `operadores` INT NOT NULL DEFAULT 1,
  `estoque_baixado` TINYINT NOT NULL DEFAULT 0,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_op_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_op_pedido` FOREIGN KEY (`pedido_venda_id`) REFERENCES `pedidos_venda` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_op_produto` FOREIGN KEY (`produto_modelo_id`) REFERENCES `produtos_modelos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_op_oficina` FOREIGN KEY (`oficina_faccao_id`) REFERENCES `oficinas_faccoes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Ordens de Corte
CREATE TABLE IF NOT EXISTS `ordens_corte` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `ordem_producao_id` INT NOT NULL,
  `tamanho` VARCHAR(20) NOT NULL,
  `quantidade_cortada` INT NOT NULL DEFAULT 0,
  `responsavel` VARCHAR(100) NOT NULL,
  `data_corte` DATE NOT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_corte_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_corte_op` FOREIGN KEY (`ordem_producao_id`) REFERENCES `ordens_producao` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Chão de Fábrica (Etapas)
CREATE TABLE IF NOT EXISTS `chao_fabrica_etapas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `ordem_producao_id` INT NOT NULL,
  `etapa` ENUM('corte', 'costura', 'acabamento', 'revisão', 'embalagem') NOT NULL,
  `status` ENUM('pendente', 'em andamento', 'conclúido') NOT NULL DEFAULT 'pendente',
  `responsavel` VARCHAR(100) NULL,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_cf_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cf_op` FOREIGN KEY (`ordem_producao_id`) REFERENCES `ordens_producao` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Retornos de Facção
CREATE TABLE IF NOT EXISTS `retornos_faccao` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `ordem_producao_id` INT NOT NULL,
  `quantidade_enviada` INT NOT NULL DEFAULT 0,
  `quantidade_retornada_boa` INT NOT NULL DEFAULT 0,
  `quantidade_defeito_perda` INT NOT NULL DEFAULT 0,
  `data_retorno` DATE NOT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_retornos_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_retornos_op` FOREIGN KEY (`ordem_producao_id`) REFERENCES `ordens_producao` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Movimentações de Estoque
CREATE TABLE IF NOT EXISTS `estoque_movimentacoes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `tipo_item` ENUM('materia_prima', 'produto_acabado') NOT NULL,
  `item_id` INT NOT NULL, -- ID do item correspondente à tabela indicada pelo tipo_item
  `quantidade` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `tipo_movimentacao` ENUM('entrada', 'saída') NOT NULL,
  `motivo` VARCHAR(255) NOT NULL,
  `usuario_id` INT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_mov_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Controle de Qualidade
CREATE TABLE IF NOT EXISTS `controle_qualidade` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `ordem_producao_id` INT NOT NULL,
  `quantidade_aprovada` INT NOT NULL DEFAULT 0,
  `quantidade_reprovada` INT NOT NULL DEFAULT 0,
  `tipo_defeito` VARCHAR(255) NULL,
  `responsavel` VARCHAR(100) NOT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_qualidade_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_qualidade_op` FOREIGN KEY (`ordem_producao_id`) REFERENCES `ordens_producao` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Financeiro Facções
CREATE TABLE IF NOT EXISTS `financeiro_faccoes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `oficina_faccao_id` INT NOT NULL,
  `retorno_faccao_id` INT NULL,
  `valor_devido` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `valor_pago` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `status` ENUM('pendente', 'pago') NOT NULL DEFAULT 'pendente',
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_fin_faccao_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fin_faccao_oficina` FOREIGN KEY (`oficina_faccao_id`) REFERENCES `oficinas_faccoes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fin_faccao_retorno` FOREIGN KEY (`retorno_faccao_id`) REFERENCES `retornos_faccao` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Financeiro dos Clientes (Mensalidades SaaS)
CREATE TABLE IF NOT EXISTS `tenants_financeiro` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `valor` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `mes_referencia` VARCHAR(7) NOT NULL, -- formato YYYY-MM
  `status_pagamento` ENUM('pendente', 'pago') NOT NULL DEFAULT 'pendente',
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_fin_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Logs de Auditoria (Impersonation)
CREATE TABLE IF NOT EXISTS `logs_auditoria` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `superadmin_email` VARCHAR(255) NOT NULL,
  `tenant_id` INT NOT NULL,
  `acao` VARCHAR(255) NOT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_logs_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. Variantes de Cores e Tamanhos dos Modelos (Grade de Variantes)
CREATE TABLE IF NOT EXISTS `produtos_variantes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `produto_modelo_id` INT NOT NULL,
  `cor` VARCHAR(50) NOT NULL,
  `tamanho` VARCHAR(20) NOT NULL,
  `estoque_atual` INT NOT NULL DEFAULT 0,
  `estoque_minimo` INT NOT NULL DEFAULT 0,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_variante_tenant_modelo_cor_tamanho` (`tenant_id`, `produto_modelo_id`, `cor`, `tamanho`),
  CONSTRAINT `fk_variantes_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_variantes_modelo` FOREIGN KEY (`produto_modelo_id`) REFERENCES `produtos_modelos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

