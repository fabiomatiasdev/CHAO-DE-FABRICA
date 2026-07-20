-- Dados Iniciais de Seed para o Sistema de PCP (SQLite)

-- Inserir Tenant de Teste
INSERT INTO `tenants` (`id`, `nome`, `cnpj`, `plano`, `mensalidade`, `status_pagamento`) 
VALUES (1, 'Confecções Alpha Ltda', '12.345.678/0001-99', 'Premium', 299.90, 'em dia');

-- Inserir Usuário Admin do Tenant (senha: admin123)
INSERT INTO `users` (`id`, `tenant_id`, `nome`, `email`, `senha`, `role`, `status`) 
VALUES (1, 1, 'Carlos Gestor', 'admin@alpha.com', '$2y$10$.ufyyeQhW.YWw2J3dlSur.ytI0xIW0bfjLaS4GR9E7kFEqpLcJR0O', 'admin', 'ativo');

-- Inserir Usuário Comum do Tenant (senha: user123)
INSERT INTO `users` (`id`, `tenant_id`, `nome`, `email`, `senha`, `role`, `status`)
VALUES (2, 1, 'Maria Operadora', 'usuario@alpha.com', '$2y$10$uMSOYcO5YGJCgeXUzRu3Qusl1kkZ76pMrr4fU6Njf6qSfbbcBoc2K', 'usuario', 'ativo');

-- Inserir Modelos de Roupas
INSERT INTO `produtos_modelos` (`id`, `tenant_id`, `nome`, `referencia`, `categoria`, `imagem`, `grade_tamanhos`, `cor`, `status`)
VALUES 
(1, 1, 'Camiseta Premium de Algodão', 'CAM-001', 'Camisetas', NULL, 'P,M,G,GG', 'Azul Marinho', 'ativo'),
(2, 1, 'Calça Slim Masculina', 'CAL-002', 'Calças', NULL, '38,40,42,44', 'Preto', 'ativo'),
(3, 1, 'Vestido Linho Sol', 'VES-003', 'Vestidos', NULL, 'PP,P,M,G', 'Cru', 'ativo');

-- Inserir Matérias-Primas
INSERT INTO `materias_primas` (`id`, `tenant_id`, `nome`, `unidade_medida`, `custo_unitario`, `fornecedor`, `estoque_atual`, `estoque_minimo`)
VALUES
(1, 1, 'Tecido Algodão Penteado 30.1', 'M', 18.5000, 'TeceBem Têxtil', 120.50, 50.00),
(2, 1, 'Tecido Sarja Flex', 'M', 25.0000, 'Tear Têxtil', 80.00, 30.00),
(3, 1, 'Zíper Invisível 15cm', 'UN', 1.2000, 'Aviamentos Express', 150.00, 50.00),
(4, 1, 'Botão de Poliéster 10mm', 'UN', 0.1500, 'Aviamentos Express', 400.00, 100.00),
(5, 1, 'Linha de Costura 120 poliéster', 'UN', 4.5000, 'Linhas Círculo', 15.00, 5.00);

-- Inserir Fichas Técnicas
INSERT INTO `fichas_tecnicas` (`id`, `tenant_id`, `produto_modelo_id`, `tempo_padrao`, `custo_mao_obra`)
VALUES
(1, 1, 1, 15, 4.50),
(2, 1, 2, 35, 12.00);

-- Inserir Itens das Fichas Técnicas
INSERT INTO `fichas_tecnicas_itens` (`tenant_id`, `ficha_tecnica_id`, `materia_prima_id`, `quantidade`)
VALUES
(1, 1, 1, 0.8500),
(1, 1, 5, 0.0500),
(1, 2, 2, 1.2000),
(1, 2, 3, 1.0000),
(1, 2, 4, 1.0000),
(1, 2, 5, 0.1000);

-- Inserir Oficinas / Facções
INSERT INTO `oficinas_faccoes` (`id`, `tenant_id`, `nome`, `cnpj_cpf`, `endereco`, `contato`, `capacidade_produtiva`, `mao_obra_peca`, `status`)
VALUES
(1, 1, 'Oficina da Costura Bella', '11.222.333/0001-44', 'Rua dos Tecelões, 123', 'Maria Costureira (11) 99999-8888', 1200, 5.00, 'ativo'),
(2, 1, 'Facção Rapidex', '22.333.444/0001-55', 'Av. das Rendas, 456', 'Roberto (11) 98888-7777', 800, 4.80, 'ativo');

-- Inserir Pedidos de Venda
INSERT INTO `pedidos_venda` (`id`, `tenant_id`, `cliente`, `produto_modelo_id`, `quantidade`, `tamanho`, `prazo_entrega`, `status`)
VALUES
(1, 1, 'Lojas Brasileiras S.A.', 1, 200, 'M', '2026-07-20', 'pendente'),
(2, 1, 'Boutique Elegance', 2, 50, '40', '2026-07-25', 'pendente');

-- Inserir Custos Industriais (Mês Referência 2026-07)
INSERT INTO `custos_industriais` (`tenant_id`, `descricao`, `valor`, `tipo`, `mes_referencia`)
VALUES
(1, 'Energia Elétrica Fábrica', 850.00, 'indireto', '2026-07'),
(1, 'Aluguel do Galpão', 2500.00, 'fixo', '2026-07'),
(1, 'Manutenção de Máquinas', 350.00, 'indireto', '2026-07');

-- Inserir Ordens de Produção (OP)
INSERT INTO `ordens_producao` (`id`, `tenant_id`, `pedido_venda_id`, `produto_modelo_id`, `oficina_faccao_id`, `quantidade`, `prazo`, `status`)
VALUES
(1, 1, 1, 1, 1, 200, '2026-07-18', 'em andamento'),
(2, 1, 2, 2, 2, 50, '2026-07-23', 'aberta');

-- Inserir Ordens de Corte
INSERT INTO `ordens_corte` (`tenant_id`, `ordem_producao_id`, `tamanho`, `quantidade_cortada`, `responsavel`, `data_corte`)
VALUES
(1, 1, 'M', 200, 'Mestre Cortador João', '2026-07-05');

-- Inserir Etapas de Chão de Fábrica para a OP 1
INSERT INTO `chao_fabrica_etapas` (`tenant_id`, `ordem_producao_id`, `etapa`, `status`, `responsavel`)
VALUES
(1, 1, 'corte', 'conclúido', 'João Cortador'),
(1, 1, 'costura', 'em andamento', 'Bella Oficina'),
(1, 1, 'acabamento', 'pendente', NULL),
(1, 1, 'revisão', 'pendente', NULL),
(1, 1, 'embalagem', 'pendente', NULL);

-- Inserir Faturamento Financeiro do Tenant (Mensalidades Cobradas pelo Superadmin)
INSERT INTO `tenants_financeiro` (`tenant_id`, `valor`, `mes_referencia`, `status_pagamento`)
VALUES
(1, 299.90, '2026-07', 'pago');

-- Inserir Variantes de Cores e Tamanhos dos Modelos de Exemplo
INSERT INTO `produtos_variantes` (`tenant_id`, `produto_modelo_id`, `cor`, `tamanho`, `estoque_atual`, `estoque_minimo`)
VALUES
(1, 1, 'Azul Marinho', 'P', 50, 10),
(1, 1, 'Azul Marinho', 'M', 80, 15),
(1, 1, 'Azul Marinho', 'G', 45, 10),
(1, 2, 'Preto', '38', 20, 5),
(1, 2, 'Preto', '40', 35, 8),
(1, 2, 'Preto', '42', 15, 5),
(1, 3, 'Cru', 'P', 12, 4),
(1, 3, 'Cru', 'M', 25, 6);

