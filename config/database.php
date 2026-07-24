<?php
/**
 * Database Connection Config for CCMS V1.0
 * Supports PDO MySQL and fallback to SQLite
 */

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $host = '127.0.0.1';
            $db   = 'ccms_db';
            $user = 'root';
            $pass = '';
            $charset = 'utf8mb4';

            // Try MySQL first
            try {
                $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                // If database doesn't exist yet, try connecting without dbname to create it
                try {
                    $dsnNoDb = "mysql:host=$host;charset=$charset";
                    $pdo = new PDO($dsnNoDb, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    self::$instance = new PDO($dsn, $user, $pass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]);
                } catch (PDOException $ex) {
                    // Fallback to SQLite if MySQL service is offline
                    $sqlitePath = __DIR__ . '/../database/ccms.sqlite';
                    $sqliteDir = dirname($sqlitePath);
                    if (!is_dir($sqliteDir)) {
                        mkdir($sqliteDir, 0777, true);
                    }
                    self::$instance = new PDO("sqlite:" . $sqlitePath, null, null, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]);
                    self::$instance->exec("PRAGMA foreign_keys = ON;");
                }
            }
        }
        return self::$instance;
    }
}
