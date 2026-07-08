# Sistema de PCP para Confecção Multi-tenant

Este é um Sistema de Gestão de Produção (PCP) completo para empresas de confecção de roupas, desenvolvido em PHP estruturado no padrão MVC, com suporte a multi-tenant (múltiplos clientes isolados no mesmo banco de dados).

## 🚀 Tecnologias Utilizadas

- **Backend:** PHP 8+ com conexão PDO
- **Banco de Dados:** MySQL / MariaDB
- **Ambiente:** Dotenv para variáveis seguras (`vlucas/phpdotenv`)
- **Autoload:** Padrão PSR-4 via Composer
- **Interface Visual:** HTML5, CSS3 personalizado (Notion/Linear style) e ícones minimalistas via Lucide Icons

---

## 🛠️ Instalação e Configuração

### 1. Clonar ou Copiar os Arquivos
Certifique-se de que os arquivos do projeto estão em sua pasta de trabalho.

### 2. Instalar Dependências do Composer
No terminal da raiz do projeto, execute o comando para instalar as dependências de ambiente:
```bash
composer install
```

### 3. Configurar Variáveis de Ambiente
Crie um arquivo `.env` a partir do modelo `.env.example`:
```bash
cp .env.example .env
```
Abra o `.env` e defina suas credenciais locais de banco de dados e do superadmin:
```env
DB_CONNECTION=sqlite
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pcp_confeccao
DB_USERNAME=root
DB_PASSWORD=

SESSION_SECRET=uma_chave_segura

# Credenciais de acesso ao Painel Superadmin
SUPERADMIN_EMAIL=superadmin@system.com
# Senha padrão: superadmin123
SUPERADMIN_PASSWORD_HASH=$2y$10$HPt7R4KYlQ/9RJ3YjYONC.BNZ.V6E1Flo7TqU7Zryhz3yMAnqx9U2
```

### 4. Configurar o Banco de Dados (SQLite / MySQL)
Por padrão, o projeto está configurado para usar o **SQLite** local como redundância automática. Para inicializar a estrutura e popular os dados de teste de imediato no SQLite, execute:
```bash
php database/init_sqlite.php
```

Se preferir usar o **MySQL/MariaDB**, basta alterar `DB_CONNECTION=mysql` no seu arquivo `.env`, importar o `database/schema.sql` e depois o `database/seed.sql` em sua instância local do MySQL.

### 5. Rodar o Projeto Localmente
Inicie o servidor embutido do PHP apontando para o diretório público (`public`):
```bash
php -S localhost:8000 -t public
```
Acesse no seu navegador: [http://localhost:8000](http://localhost:8000)

---

## 🔐 Níveis de Acesso para Testes

### 1. Superadmin (Dono do SaaS)
- **E-mail:** `superadmin@system.com`
- **Senha:** `superadmin123`
- **Área:** Gestão de empresas clientes (tenants), histórico de mensalidades e botão **"Acessar como Cliente" (Impersonation)**.

### 2. Administrador do Tenant (Empresa de Exemplo)
- **E-mail:** `admin@alpha.com`
- **Senha:** `admin123`
- **Área:** Dashboard e controle operacional completo de sua fábrica.

### 3. Usuário Operacional
- **E-mail:** `usuario@alpha.com`
- **Senha:** `admin123`
- **Área:** Acesso operacional básico de chão de fábrica.
