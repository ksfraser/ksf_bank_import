<?php

namespace Ksfraser\FaBankImport\Database;

class DatabaseFactoryMock
{
    public static function getConnection()
    {
        return new PDOMock();
    }
}

class PDOMock
{
    private static $lastInsertId = 1;

    public function prepare($query)
    {
        return new PDOStatementMock($query);
    }

    public function beginTransaction()
    {
        return true;
    }

    public function commit()
    {
        return true;
    }

    public function rollBack()
    {
        return true;
    }

    public function inTransaction()
    {
        return true;
    }

    public function lastInsertId()
    {
        return (string)self::$lastInsertId++;
    }
}

class PDOStatementMock
{
    private $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function execute($params = [])
    {
        return \db_query($this->query, $params);
    }

    public function fetch($fetch_style = null)
    {
        $result = \db_query($this->query);
        return is_array($result) && !empty($result) ? $result[0] : false;
    }

    public function fetchAll($fetch_style = null)
    {
        $result = \db_query($this->query);
        return is_array($result) ? $result : [];
    }
}