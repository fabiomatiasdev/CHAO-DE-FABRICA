# PROMPT PARA ANTIGRAVITY IDE — Sistema de Gestão de Produção (PCP) para Confecção

Copie e cole o conteúdo abaixo inteiro no Antigravity IDE como instrução inicial do projeto.

---

## CONTEXTO DO PROJETO

Construa um **Sistema de Gestão de Produção (PCP)** completo para empresas de confecção (roupas), no modelo **multi-tenant** (multi-cliente / SaaS), onde cada cliente (tenant) tem seus próprios dados isolados, mas todos rodam na mesma aplicação e no mesmo banco de dados.

O sistema deve ser **profissional, limpo, organizado**, com tema **light (claro)** e cor principal **azul**, no padrão de sistemas SaaS modernos (ex: estilo Linear, Notion, sistemas ERP atuais). Sidebar fixa à esquerda com ícones + texto, cards com sombra leve, bordas arredondadas, tipografia limpa (Inter ou similar).

---

## 1. STACK TÉCNICA (obrigatória)

- **Backend:** PHP puro ou PHP + um micro-framework leve (ex: Slim ou PHP nativo com PDO), organizado em MVC.
- **Banco de dados:** MySQL/MariaDB, com todo o schema em **SQL** (arquivo `.sql` de criação completo, com `CREATE TABLE`, chaves estrangeiras, índices e dados de seed inicial).
- **Frontend:** HTML5 + CSS3 + JavaScript (pode usar Bootstrap 5 ou Tailwind como base, mas com customização visual própria — não pode parecer template genérico).
- **Autenticação:** sessões PHP (`$_SESSION`) + senhas com `password_hash()` / `password_verify()`.
- **Configuração sensível:** usar arquivo `.env` (com biblioteca tipo `vlucas/phpdotenv` via Composer) para variáveis de ambiente, credenciais de banco e **credenciais do superadmin**.
- **Estrutura de pastas sugerida:**
```
/app
  /Controllers
  /Models
  /Views
  /Middlewares
/config
/database
  schema.sql
  seed.sql
/public
  index.php
  /assets (css, js, imagens)
.env
.env.example
composer.json
```

---

## 2. MODELO MULTI-TENANT E HIERARQUIA DE USUÁRIOS

O sistema tem **3 níveis de acesso**:

### 2.1 Superadmin (dono do sistema / você)
- É o nível mais alto, único e global (não pertence a nenhum tenant específico).
- **Credenciais do superadmin devem vir do arquivo `.env`** (ex: `SUPERADMIN_EMAIL`, `SUPERADMIN_PASSWORD_HASH`), não devem estar cadastradas como um registro comum na tabela de usuários dependente de tenant — deve ser validado no login de forma especial, comparando com o `.env`.
- Painel exclusivo do superadmin, separado do painel operacional das empresas, contendo:
  - **Gestão de Clientes (Tenants):** criar, editar, ativar/desativar empresas clientes.
  - **Financeiro dos Clientes:** para cada tenant, controlar plano contratado, valor da mensalidade, status de pagamento (em dia, atrasado, cancelado), histórico de cobranças/recebimentos.
  - **Botão "Acessar como Cliente" (Impersonation):** em cada tenant listado, um botão que permite ao superadmin entrar no sistema daquele cliente **sem precisar da senha**, assumindo temporariamente uma sessão como se fosse o admin daquele tenant. Deve haver um indicador visual no topo (ex: uma barra amarela/azul fixa dizendo "Você está acessando como [Nome do Cliente] — Voltar ao modo Superadmin") e um botão para retornar com segurança ao painel superadmin, encerrando a sessão de impersonation. Esse acesso deve ser registrado em log de auditoria (quem acessou, qual tenant, data/hora).

### 2.2 Admin (dono/gestor da empresa cliente — o tenant)
- Enxerga e opera apenas os dados do seu próprio tenant.
- **Pode criar, editar e desativar usuários** dentro da sua empresa (funcionários, operadores, etc.), definindo permissões básicas de acesso aos módulos.
- Tem acesso completo a todos os módulos operacionais listados abaixo.

### 2.3 Usuário comum
- Criado pelo admin, com acesso restrito conforme permissões definidas (ex: só Chão de Fábrica, ou só Estoque).

**Regra de isolamento:** toda tabela operacional deve ter uma coluna `tenant_id`, e todas as queries devem sempre filtrar pelo tenant do usuário logado (nunca deixar vazar dados entre clientes).

---

## 3. MENU / MÓDULOS DO SISTEMA

Estruture a sidebar exatamente com estas seções e itens (ordem e agrupamento como abaixo):

### PAINEL
- **Dashboard Geral**

### CADASTROS
- **Modelos de Produtos** — cadastro de modelos/produtos de roupas (nome, referência, categoria, imagem, grade de tamanhos, cor, status ativo/inativo).
- **Matérias-Primas** — cadastro de insumos (tecidos, aviamentos, linhas, etc.): nome, unidade de medida, custo unitário, fornecedor, estoque atual, estoque mínimo.
- **Fichas Técnicas** — ficha técnica de cada modelo (Bill of Materials): lista de matérias-primas e quantidade consumida por peça/tamanho, tempo padrão de produção, custo de mão de obra estimado. Deve permitir vincular ficha técnica a um Modelo de Produto.
- **Oficinas / Facções** — cadastro das oficinas terceirizadas (facções): nome, CNPJ/CPF, endereço, contato, capacidade produtiva, valor de mão de obra por peça, status.

### COMERCIAL
- **Controle de Custos** — apuração de custo total por modelo/OP (matéria-prima + mão de obra + custos industriais).
- **Custos Industriais** — cadastro de custos fixos/indiretos de fábrica (energia, aluguel, administrativo) e rateio por produção.
- **Pedidos de Venda** — cadastro de pedidos de clientes finais: cliente, modelo, quantidade, tamanho, prazo de entrega, status (pendente, em produção, entregue).
- **Financeiro Facções** — controle financeiro do que é devido/pago a cada oficina/facção com base nas peças produzidas e retornadas (contas a pagar por facção).

### PCP (Planejamento e Controle de Produção)
- **Ordens de Produção (OP)** — criação e acompanhamento de ordens de produção vinculadas a um modelo/pedido, com quantidade, prazo, status (aberta, em andamento, concluída, cancelada), e vínculo com a facção responsável.
- **Ordens de Corte** — controle do corte do tecido: quantidade cortada por tamanho, data, responsável, vínculo com a OP.
- **Chão de Fábrica** — acompanhamento das etapas de produção internas (corte → costura → acabamento → revisão → embalagem), com apontamento de status por etapa.
- **Retornos de Facção** — registro do retorno das peças enviadas à facção: quantidade entregue à facção, quantidade retornada boa, quantidade com defeito/perda, data do retorno.

### ESTOQUE
- **Ajuste de Estoque** — tela para ajuste manual de estoque de matérias-primas e produtos acabados (entrada/saída manual com motivo/justificativa).

### RELATÓRIOS
- **Curva ABC** — classificação ABC de produtos/matérias-primas por valor ou volume (A = maior relevância, B = média, C = baixa).
- **Relatório de Perdas** — relatório consolidado de perdas de produção (quantidade e valor financeiro), filtrável por período, modelo e facção.
- **Diagnóstico da OP** — relatório detalhado de uma Ordem de Produção específica: previsto x realizado, tempo, custo, perdas, eficiência.
- **Controle de Qualidade** — registro de inspeções de qualidade, com quantidade aprovada/reprovada, tipo de defeito e responsável pela inspeção.

---

## 4. DASHBOARD GERAL (tela inicial)

Reproduza os seguintes cards/indicadores no topo e no corpo da tela, exatamente com esta lógica:

**Linha 1 de cards:**
1. **Produtos Acabados** — número total de modelos ativos (label: "Modelos Ativos").
2. **Matérias-Primas** — número total de itens de matéria-prima cadastrados (label: "Itens em Estoque").
3. **OPs em Andamento** — quantidade de Ordens de Produção com status "em andamento", com uma barra de progresso mostrando "Carga de Fábrica" em % (capacidade utilizada vs. capacidade total das facções/fábrica).
4. **Valor em Estoque** — valor monetário total em estoque (matéria-prima + produtos acabados), com subtexto mostrando o custo total.

**Linha 2 de cards:**
5. **Total Produzido** — soma de unidades produzidas no período (label "Sem Histórico" quando zerado).
6. **Total Perdido** — soma de unidades perdidas por problema de qualidade (label "Qualidade").
7. **Valor das Perdas** — impacto financeiro das perdas em R$.
8. **Eficiência Produtiva** — percentual (produzido bom / total produzido), com barra de progresso e uma meta configurável (ex: "Meta: 95%").

**Linha 3 (dois blocos lado a lado):**
- **Ordens de Produção Recentes** — lista das últimas OPs criadas, com link "Ver Todas". Estado vazio: "Nenhuma OP cadastrada."
- **Movimentações de Estoque** — lista das últimas movimentações, com link "Ajustar" (leva para tela de Ajuste de Estoque). Estado vazio: "Nenhuma movimentação."

**Linha 4 (tabela full-width):**
- **Produção por Período** — tabela com colunas "Período (Mês/Ano)" e "Quantidade Produzida", agregando a produção mês a mês. Estado vazio: "Nenhum dado produtivo."

No topo direito da tela, mostrar o **Tenant ID** do cliente logado e a **data atual**, como no layout de referência.

Todos os valores devem ser calculados dinamicamente via queries no banco (não mockados), filtrados sempre pelo `tenant_id` da sessão atual.

---

## 5. DESIGN / UI

- Tema **light**, fundo cinza bem claro (`#F5F7FA` ou similar) para a área de conteúdo, cards brancos com sombra suave e cantos arredondados (`border-radius: 12px`).
- Cor primária **azul** (ex: `#2563EB` ou `#1D4ED8`) para botões, links, ícones ativos e destaques (como o item de menu ativo com fundo azul sólido e texto branco, igual ao exemplo "Fichas Técnicas" destacado).
- Sidebar com seções em maiúsculo cinza (ex: "PAINEL", "CADASTROS", "COMERCIAL", "PCP", "ESTOQUE", "RELATÓRIOS") como títulos de grupo, sem fundo, e itens abaixo com ícone + texto.
- Tipografia limpa tipo Inter/Roboto, tamanhos consistentes, bom espaçamento (padding generoso, não "espremido").
- Indicadores de status com cores semânticas: verde (positivo/ativo), vermelho (perdas/alerta), azul (neutro/informativo), amarelo (atenção).
- Responsivo (funcionar bem em desktop e tablet no mínimo).
- Ícones no estilo outline/minimalista (ex: Lucide Icons ou Font Awesome light).

---

## 6. REQUISITOS TÉCNICOS ADICIONAIS

- Gerar o **schema SQL completo** (`schema.sql`) com todas as tabelas necessárias para suportar os módulos acima, incluindo no mínimo:
  `tenants`, `users` (com campo `role`: admin/usuario e `tenant_id`), `produtos_modelos`, `materias_primas`, `fichas_tecnicas`, `fichas_tecnicas_itens`, `oficinas_faccoes`, `pedidos_venda`, `custos_industriais`, `ordens_producao`, `ordens_corte`, `chao_fabrica_etapas`, `retornos_faccao`, `estoque_movimentacoes`, `controle_qualidade`, `financeiro_faccoes`, `tenants_financeiro` (cobrança dos clientes pelo superadmin), `logs_auditoria` (para registrar acessos de impersonation).
- Todas as tabelas operacionais devem ter `tenant_id` com chave estrangeira, exceto tabelas do superadmin.
- Implementar **middleware de autenticação e autorização** por papel (superadmin / admin / usuário) em todas as rotas.
- Implementar o **log de auditoria** de impersonation (superadmin acessando como cliente).
- Criar arquivo `.env.example` documentando todas as variáveis necessárias (conexão do banco, credenciais do superadmin, chave de sessão, etc.), e nunca commitar o `.env` real.
- Criar um **seed inicial** com um tenant de exemplo, um admin de exemplo e um superadmin configurado via `.env`.
- Todo o CRUD dos módulos de cadastro deve ter: listagem com busca/filtro, criação, edição, exclusão (com confirmação) e validação de campos obrigatórios no backend.

---

## 7. ENTREGÁVEIS ESPERADOS

1. Projeto PHP completo, organizado em MVC, pronto para rodar localmente (ex: com PHP embutido `php -S` ou Apache/Nginx).
2. Arquivo `schema.sql` completo e comentado.
3. Arquivo `.env.example`.
4. Tela de login única, que identifica automaticamente se é superadmin (via `.env`), admin ou usuário comum, redirecionando para o painel correto.
5. Painel Superadmin funcional (gestão de tenants, financeiro dos clientes, impersonation).
6. Painel operacional (Dashboard + todos os módulos do menu) funcionando com dados reais do banco, filtrados por tenant.
7. README explicando como instalar, configurar o `.env` e rodar o projeto.

---

Construa o sistema seguindo rigorosamente esta especificação, priorizando primeiro a estrutura do banco de dados, depois autenticação e multi-tenant, depois o Dashboard, e por fim os demais módulos do menu.
