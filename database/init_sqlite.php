<?php

$sqlitePath = __DIR__ . '/pcp_confeccao.sqlite';

// Remover arquivo anterior se existir para começar limpo
if (file_exists($sqlitePath)) {
    unlink($sqlitePath);
}

try {
    $db = new PDO("sqlite:" . $sqlitePath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("PRAGMA foreign_keys = ON;");

    echo "Conexão SQLite criada com sucesso!\n";

    // 1. Importar Schema
    $schemaSql = file_get_contents(__DIR__ . '/schema_sqlite.sql');
    // Dividir os comandos por ponto e vírgula
    $commands = explode(';', $schemaSql);
    foreach ($commands as $cmd) {
        $cmd = trim($cmd);
        if (!empty($cmd)) {
            $db->exec($cmd);
        }
    }
    echo "Estrutura do banco de dados (schema) criada com sucesso!\n";

    // 2. Importar Seed
    $seedSql = file_get_contents(__DIR__ . '/seed_sqlite.sql');
    $commands = explode(';', $seedSql);
    foreach ($commands as $cmd) {
        $cmd = trim($cmd);
        if (!empty($cmd)) {
            $db->exec($cmd);
        }
    }
    echo "Dados de exemplo (seed) importados com sucesso!\n";
    echo "Banco de dados SQLite inicializado com sucesso em database/pcp_confeccao.sqlite!\n";

} catch (Exception $e) {
    echo "Erro ao inicializar banco: " . $e->getMessage() . "\n";
    exit(1);
}
