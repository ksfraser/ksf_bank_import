<?php

namespace Ksfraser\FaBankImport\Database;

use Ksfraser\FaBankImport\Config\Config;

/**
 * @BABOK CLASS ROLLUP: DatabaseFactory — 3 methods; FR-008 (3); DR-003 (3)
 * @BABOK RTM: FR-008 / DR-003; file: Database/DatabaseFactory.php; coverage: docs/TestCases/ (file rollup)
 */
class DatabaseFactory
{
    private static $connection = null;
    private static $config;

    /** @BABOK FR-008 / DR-003 — method: getConnection */
    public static function getConnection()
    {
        if (self::$connection === null) {
            self::$config = Config::getInstance();
            self::$connection = self::createConnection();
        }
        return self::$connection;
    }

    /** @BABOK FR-008 / DR-003 — method: createConnection */
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

    /** @BABOK FR-008 / DR-003 — method: closeConnection */
    public static function closeConnection(): void
    {
        self::$connection = null;
    }
}