<?php
/**
 * CarePoint Pro - Hospital Management System
 * Database Connection Handler (PDO Singleton)
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;
    private static bool $connected = false;
    private static ?string $errorMessage = null;

    /**
     * Get Database Connection
     */
    public static function getConnection(?string $host = null, ?string $dbname = null, ?string $user = null, ?string $pass = null, ?string $port = null): ?PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $host = $host ?? DB_HOST;
        $port = $port ?? DB_PORT;
        $dbname = $dbname ?? DB_NAME;
        $user = $user ?? DB_USER;
        $pass = $pass ?? DB_PASS;
        $charset = DB_CHARSET;

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            self::$instance = new PDO($dsn, $user, $pass, $options);
            self::$connected = true;
            return self::$instance;
        } catch (PDOException $e) {
            self::$connected = false;
            self::$errorMessage = $e->getMessage();
            return null;
        }
    }

    /**
     * Get Raw PDO server connection without selecting database (used during installation)
     */
    public static function getServerConnection(string $host, string $user, string $pass, string $port = '3306'): ?PDO {
        $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
            return new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            self::$errorMessage = $e->getMessage();
            return null;
        }
    }

    public static function isConnected(): bool {
        if (self::$instance === null) {
            self::getConnection();
        }
        return self::$connected;
    }

    public static function getErrorMessage(): ?string {
        return self::$errorMessage;
    }
}
