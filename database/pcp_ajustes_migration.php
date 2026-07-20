<?php

/**
 * Migração de Banco de Dados para Ajustes PCP
 * Funciona para SQLite e MySQL
 */

require_once __DIR__ . '/../app/Core/Database.php';
use App\Core\Database;

echo "Iniciando migração de banco de dados para Ajustes PCP...\n";

try {
    $db = Database::getConnection();
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "Driver do Banco de Dados: {$driver}\n";

    // 1. Tabela de Locais de Estoque (Armazenadores)
    if ($driver === 'sqlite') {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `locais_estoque` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `tenant_id` INTEGER NOT NULL,
              `nome` VARCHAR(255) NOT NULL,
              `tipo` VARCHAR(50) NOT NULL DEFAULT 'acabados',
              `descricao` TEXT NULL,
              `status` VARCHAR(50) NOT NULL DEFAULT 'ativo',
              `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
            );
        ");
    } else {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `locais_estoque` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `tenant_id` INT NOT NULL,
              `nome` VARCHAR(255) NOT NULL,
              `tipo` VARCHAR(50) NOT NULL DEFAULT 'acabados',
              `descricao` TEXT NULL,
              `status` VARCHAR(50) NOT NULL DEFAULT 'ativo',
              `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
    echo "[OK] Tabela 'locais_estoque' verificada/criada.\n";

    // Insert local de estoque padrão caso não exista nenhum por tenant
    $tenants = Database::fetchAll("SELECT id FROM tenants");
    foreach ($tenants as $t) {
        $exist = Database::fetch(
            "SELECT id FROM locais_estoque WHERE tenant_id = :tenant_id AND nome = 'Estoque Acabado Principal'",
            ['tenant_id' => $t['id']]
        );
        if (!$exist) {
            Database::query(
                "INSERT INTO locais_estoque (tenant_id, nome, tipo, descricao) VALUES (:t, 'Estoque Acabado Principal', 'acabados', 'Local de armazenamento padrão para produtos finalizados')",
                ['t' => $t['id']]
            );
            Database::query(
                "INSERT INTO locais_estoque (tenant_id, nome, tipo, descricao) VALUES (:t, 'Almoxarifado de Insumos', 'insumos', 'Local de armazenamento padrão para matérias-primas e tecidos')",
                ['t' => $t['id']]
            );
        }
    }

    // 2. Tabela de Envios para Facção
    if ($driver === 'sqlite') {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `envios_faccao` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `tenant_id` INTEGER NOT NULL,
              `ordem_producao_id` INTEGER NOT NULL,
              `ordem_corte_id` INTEGER NULL,
              `oficina_faccao_id` INTEGER NOT NULL,
              `quantidade_enviada` INTEGER NOT NULL DEFAULT 0,
              `etapa_destino` VARCHAR(100) NOT NULL DEFAULT 'Costura',
              `data_envio` DATE NOT NULL,
              `status` VARCHAR(50) NOT NULL DEFAULT 'enviado',
              `observacoes` TEXT NULL,
              `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
              FOREIGN KEY (`ordem_producao_id`) REFERENCES `ordens_producao` (`id`) ON DELETE CASCADE,
              FOREIGN KEY (`oficina_faccao_id`) REFERENCES `oficinas_faccoes` (`id`) ON DELETE CASCADE
            );
        ");
    } else {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `envios_faccao` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `tenant_id` INT NOT NULL,
              `ordem_producao_id` INT NOT NULL,
              `ordem_corte_id` INT NULL,
              `oficina_faccao_id` INT NOT NULL,
              `quantidade_enviada` INT NOT NULL DEFAULT 0,
              `etapa_destino` VARCHAR(100) NOT NULL DEFAULT 'Costura',
              `data_envio` DATE NOT NULL,
              `status` VARCHAR(50) NOT NULL DEFAULT 'enviado',
              `observacoes` TEXT NULL,
              `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
              FOREIGN KEY (`ordem_producao_id`) REFERENCES `ordens_producao` (`id`) ON DELETE CASCADE,
              FOREIGN KEY (`oficina_faccao_id`) REFERENCES `oficinas_faccoes` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
    echo "[OK] Tabela 'envios_faccao' verificada/criada.\n";

    // Helper para adicionar colunas se não existirem
    $addColumnIfMissing = function ($table, $column, $definition) use ($db, $driver) {
        try {
            if ($driver === 'sqlite') {
                $cols = $db->query("PRAGMA table_info(`$table`)")->fetchAll(PDO::FETCH_ASSOC);
                $exists = false;
                foreach ($cols as $c) {
                    if ($c['name'] === $column) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $db->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
                    echo "[OK] Coluna '$column' adicionada na tabela '$table'.\n";
                }
            } else {
                $cols = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'")->fetchAll(PDO::FETCH_ASSOC);
                if (empty($cols)) {
                    $db->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
                    echo "[OK] Coluna '$column' adicionada na tabela '$table'.\n";
                }
            }
        } catch (Exception $ex) {
            echo "[INFO] Notificação ao alterar '$table'.'$column': " . $ex->getMessage() . "\n";
        }
    };

    // Colunas extras em ordens_producao
    $addColumnIfMissing('ordens_producao', 'local_estoque_id', 'INTEGER NULL');
    $addColumnIfMissing('ordens_producao', 'consumo_real_registrado', 'TINYINT DEFAULT 0');

    // Colunas extras em ordens_corte
    $addColumnIfMissing('ordens_corte', 'materia_prima_id', 'INTEGER NULL');
    $addColumnIfMissing('ordens_corte', 'quantidade_real_utilizada', 'DECIMAL(10, 4) DEFAULT 0.0000');
    $addColumnIfMissing('ordens_corte', 'unidade_medida', 'VARCHAR(20) NULL');
    $addColumnIfMissing('ordens_corte', 'status', "VARCHAR(50) DEFAULT 'concluido'");

    // Colunas extras em retornos_faccao
    $addColumnIfMissing('retornos_faccao', 'envio_faccao_id', 'INTEGER NULL');
    $addColumnIfMissing('retornos_faccao', 'etapas_concluidas', "VARCHAR(255) NULL");
    $addColumnIfMissing('retornos_faccao', 'motivo_defeito', 'TEXT NULL');

    // Colunas extras em estoque_movimentacoes
    $addColumnIfMissing('estoque_movimentacoes', 'local_estoque_id', 'INTEGER NULL');

    echo "Migração concluída com sucesso!\n";

} catch (Exception $e) {
    echo "Erro na migração: " . $e->getMessage() . "\n";
    exit(1);
}
