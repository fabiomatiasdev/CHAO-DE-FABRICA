<?php

require_once __DIR__ . '/../vendor/autoload.php';

try {
    $dbFile = __DIR__ . '/pcp_confeccao.sqlite';
    if (!file_exists($dbFile)) {
        throw new Exception("Banco de dados SQLite não encontrado em: $dbFile");
    }

    $db = new PDO("sqlite:$dbFile");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Criar a tabela produtos_variantes
    $db->exec("
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
    ");

    echo "Tabela 'produtos_variantes' criada com sucesso no SQLite!\n";
} catch (Exception $e) {
    echo "ERRO NA MIGRAÇÃO: " . $e->getMessage() . "\n";
    exit(1);
}
