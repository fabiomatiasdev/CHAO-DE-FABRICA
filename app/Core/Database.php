<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $connection = $_ENV['DB_CONNECTION'] ?? 'mysql';
            $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
            $port = $_ENV['DB_PORT'] ?? '3306';
            $db   = $_ENV['DB_DATABASE'] ?? 'pcp_confeccao';
            $user = $_ENV['DB_USERNAME'] ?? 'root';
            $pass = $_ENV['DB_PASSWORD'] ?? '';
            $charset = 'utf8mb4';

            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                if ($connection === 'sqlite') {
                    throw new PDOException("Forçar SQLite via configuração");
                }
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                // Fallback automático para SQLite local
                $sqlitePath = __DIR__ . '/../../database/pcp_confeccao.sqlite';
                $optionsSqlite = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ];
                try {
                    self::$instance = new PDO("sqlite:" . $sqlitePath, null, null, $optionsSqlite);
                    self::$instance->exec("PRAGMA foreign_keys = ON;");
                } catch (PDOException $ex) {
                    throw new PDOException(
                        "Falha ao conectar ao MySQL (" . $e->getMessage() . ") e fallback SQLite também falhou: " . $ex->getMessage(),
                        (int)$ex->getCode()
                    );
                }
            }
        }

        return self::$instance;
    }

    /**
     * Atalho para preparar e executar query rápida.
     */
    public static function query(string$sql, array $params = []): \PDOStatement
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Atalho para buscar uma única linha.
     */
    public static function fetch(string $sql, array $params = []): ?array
    {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Atalho para buscar todas as linhas.
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * Retorna o ID do último registro inserido.
     */
    public static function lastInsertId(): string
    {
        return self::getConnection()->lastInsertId();
    }

    /**
     * Retorna a expressão de formatação de data compatível com o driver ativo (MySQL ou SQLite).
     */
    public static function dateFormat(string $column, string $format): string
    {
        $driver = self::getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            return "strftime('{$format}', {$column})";
        }
        return "DATE_FORMAT({$column}, '{$format}')";
    }
}
