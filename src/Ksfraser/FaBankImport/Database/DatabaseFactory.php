<?php

namespace Ksfraser\FaBankImport\Database;

use Ksfraser\FaBankImport\Config\Config;

/**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
 */
class DatabaseFactory
{
    private static $connection = null;
    private static $config;

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    public static function getConnection()
    {
        if (self::$connection === null) {
            self::$config = Config::getInstance();
            self::$connection = self::createConnection();
        }
        return self::$connection;
    }

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    private static function createConnection()
    {
        $host = self::$config->get('db.host');
        $name = self::$config->get('db.name');
        $user = self::$config->get('db.user');
        $pass = self::$config->get('db.pass');

        try {
            $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ];
            return new \PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Connection failed: " . $e->getMessage());
        }
    }

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    public static function closeConnection(): void
    {
        self::$connection = null;
    }
}