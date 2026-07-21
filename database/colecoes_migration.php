<?php

/**
 * Migration: Tabelas para o Módulo de Calendário de Coleções & MRP
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar .env se existir
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

use App\Core\Database;

try {
    $db = Database::getConnection();
    echo "=== Iniciando Migração: Calendário de Coleções & MRP ===\n";

    // Detectar motor do PDO
    $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `colecoes` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `tenant_id` INTEGER NOT NULL,
              `nome` TEXT NOT NULL,
              `descricao` TEXT NULL,
              `data_inicio` TEXT NOT NULL,
              `data_fim` TEXT NOT NULL,
              `status` TEXT NOT NULL DEFAULT 'planejada',
              `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS `colecoes_ops` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `tenant_id` INTEGER NOT NULL,
              `colecao_id` INTEGER NOT NULL,
              `ordem_producao_id` INTEGER NOT NULL
            );
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS `cronograma_tarefas` (
              `id` INTEGER PRIMARY KEY AUTOINCREMENT,
              `tenant_id` INTEGER NOT NULL,
              `colecao_id` INTEGER NULL,
              `titulo` TEXT NOT NULL,
              `descricao` TEXT NULL,
              `data_execucao` TEXT NOT NULL,
              `status` TEXT NOT NULL DEFAULT 'pendente',
              `responsavel` TEXT NULL,
              `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ");
    } else {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `colecoes` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `tenant_id` INT NOT NULL,
              `nome` VARCHAR(255) NOT NULL,
              `descricao` TEXT NULL,
              `data_inicio` DATE NOT NULL,
              `data_fim` DATE NOT NULL,
              `status` VARCHAR(50) NOT NULL DEFAULT 'planejada',
              `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS `colecoes_ops` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `tenant_id` INT NOT NULL,
              `colecao_id` INT NOT NULL,
              `ordem_producao_id` INT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS `cronograma_tarefas` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `tenant_id` INT NOT NULL,
              `colecao_id` INT NULL,
              `titulo` VARCHAR(255) NOT NULL,
              `descricao` TEXT NULL,
              `data_execucao` DATE NOT NULL,
              `status` VARCHAR(50) NOT NULL DEFAULT 'pendente',
              `responsavel` VARCHAR(150) NULL,
              `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    echo "=== Migração Concluída com Sucesso! ===\n";
} catch (\Exception $e) {
    echo "[ERRO] Falha na migração: " . $e->getMessage() . "\n";
}
