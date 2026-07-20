<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

try {
    // Carregar .env se não estiver carregado (caso rode via CLI puro)
    if (file_exists(__DIR__ . '/../.env')) {
        $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0)
                continue;
            list($name, $value) = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value);
        }
    }

    $db = Database::getConnection();
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "Conectado ao banco. Driver ativo: {$driver}\n";

    if ($driver === 'sqlite') {
        // Criar ordens_producao_variantes no SQLite
        $db->exec("
            CREATE TABLE IF NOT EXISTS `ordens_producao_variantes` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `tenant_id` INTEGER NOT NULL,
              `ordem_producao_id` INTEGER NOT NULL,
              `produto_variante_id` INTEGER NOT NULL,
              `quantidade` INTEGER NOT NULL DEFAULT 0,
              FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
              FOREIGN KEY (`ordem_producao_id`) REFERENCES `ordens_producao` (`id`) ON DELETE CASCADE,
              FOREIGN KEY (`produto_variante_id`) REFERENCES `produtos_variantes` (`id`) ON DELETE CASCADE
            );
        ");

        // Criar ordens_corte_variantes no SQLite
        $db->exec("
            CREATE TABLE IF NOT EXISTS `ordens_corte_variantes` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `tenant_id` INTEGER NOT NULL,
              `ordem_corte_id` INTEGER NOT NULL,
              `produto_variante_id` INTEGER NOT NULL,
              `quantidade_cortada` INTEGER NOT NULL DEFAULT 0,
              FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
              FOREIGN KEY (`ordem_corte_id`) REFERENCES `ordens_corte` (`id`) ON DELETE CASCADE,
              FOREIGN KEY (`produto_variante_id`) REFERENCES `produtos_variantes` (`id`) ON DELETE CASCADE
            );
        ");

        // Adicionar produto_variante_id na chao_fabrica_etapas (se não existir)
        try {
            $db->exec("ALTER TABLE `chao_fabrica_etapas` ADD COLUMN `produto_variante_id` INTEGER REFERENCES `produtos_variantes` (`id`) ON DELETE CASCADE");
            echo "Coluna 'produto_variante_id' adicionada com sucesso no SQLite!\n";
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'duplicate column') || str_contains($e->getMessage(), 'already exists')) {
                echo "Coluna 'produto_variante_id' já existe na tabela 'chao_fabrica_etapas'.\n";
            } else {
                throw $e;
            }
        }

    } else {
        // Criar ordens_producao_variantes no MySQL
        $db->exec("
            CREATE TABLE IF NOT EXISTS `ordens_producao_variantes` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `tenant_id` INT NOT NULL,
              `ordem_producao_id` INT NOT NULL,
              `produto_variante_id` INT NOT NULL,
              `quantidade` INT NOT NULL DEFAULT 0,
              CONSTRAINT `fk_op_var_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
              CONSTRAINT `fk_op_var_op` FOREIGN KEY (`ordem_producao_id`) REFERENCES `ordens_producao` (`id`) ON DELETE CASCADE,
              CONSTRAINT `fk_op_var_variante` FOREIGN KEY (`produto_variante_id`) REFERENCES `produtos_variantes` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Criar ordens_corte_variantes no MySQL
        $db->exec("
            CREATE TABLE IF NOT EXISTS `ordens_corte_variantes` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `tenant_id` INT NOT NULL,
              `ordem_corte_id` INT NOT NULL,
              `produto_variante_id` INT NOT NULL,
              `quantidade_cortada` INT NOT NULL DEFAULT 0,
              CONSTRAINT `fk_corte_var_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
              CONSTRAINT `fk_corte_var_corte` FOREIGN KEY (`ordem_corte_id`) REFERENCES `ordens_corte` (`id`) ON DELETE CASCADE,
              CONSTRAINT `fk_corte_var_variante` FOREIGN KEY (`produto_variante_id`) REFERENCES `produtos_variantes` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Adicionar produto_variante_id na chao_fabrica_etapas no MySQL (se não existir)
        try {
            $db->exec("ALTER TABLE `chao_fabrica_etapas` ADD COLUMN `produto_variante_id` INT NULL");
            $db->exec("ALTER TABLE `chao_fabrica_etapas` ADD CONSTRAINT `fk_cf_variante` FOREIGN KEY (`produto_variante_id`) REFERENCES `produtos_variantes` (`id`) ON DELETE CASCADE");
            echo "Coluna 'produto_variante_id' adicionada com sucesso no MySQL!\n";
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'duplicate column') || str_contains($e->getMessage(), 'already exists') || str_contains($e->getMessage(), 'Duplicate column name')) {
                echo "Coluna 'produto_variante_id' já existe na tabela 'chao_fabrica_etapas'.\n";
            } else {
                throw $e;
            }
        }
    }

    echo "MIGRAÇÃO DE MULTI-VARIANTES CONCLUÍDA COM SUCESSO!\n";

} catch (Exception $e) {
    echo "ERRO DE MIGRAÇÃO: " . $e->getMessage() . "\n";
    exit(1);
}
