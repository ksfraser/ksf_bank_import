<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Services\Support;

/**
 * In-memory stand-in for the FrontAccounting database layer.
 *
 * The production classes under test call the global db_query()/db_fetch()/
 * db_escape()/db_num_rows()/db_insert_id() helpers. The namespaced function
 * overrides installed by fa_db_stubs_services.php / fa_db_stubs_repository.php
 * delegate to this fake while it is active, so the tests never need a live
 * database.
 *
 * Responses are registered either as an ordered queue (queue()) or bound to a
 * SQL fragment (on()). A registered response may be:
 *  - an array of rows            => returned as a successful result set
 *  - false                       => the query fails (db_query() returns false)
 *  - a callable(string $sql)     => resolved at query time
 */
final class FaDbFake
{
    /** @var FaDbFake|null */
    private static $instance = null;

    /** @var array<int,array{sql:string,params:mixed}> */
    public $queries = [];

    /** @var array<int,array{pattern:string|null,result:mixed,used:bool}> */
    private $responses = [];

    /** @var int Value returned by db_insert_id(). */
    public $insertId = 1;

    /** @var bool Value returned by is_uploaded_file(). */
    public $uploadIsValid = true;

    /** @var bool Value returned by move_uploaded_file(). */
    public $moveSucceeds = true;

    /** @var bool Value returned by has_access(). */
    public $accessAllowed = true;

    /** @var array<int,array{path:string,contents:string}> */
    public $readFiles = [];

    /** @var array<int,string> */
    public $headers = [];

    /**
     * Queued answers for is_new_reference(); the last answer is reused once
     * the queue is empty. An empty queue means "always a new reference".
     *
     * @var array<int,bool>
     */
    public $referenceResults = [];

    /**
     * When true the namespaced preg_split() override reports a failure, which
     * is the only way to reach the tokenizer's defensive error branch.
     *
     * @var bool
     */
    public $pregSplitFails = false;

    /**
     * Activate the fake and return it.
     */
    public static function activate(): self
    {
        self::$instance = new self();

        return self::$instance;
    }

    /**
     * The currently active fake, or null when the real FA helpers should be used.
     */
    public static function current(): ?self
    {
        return self::$instance;
    }

    /**
     * Deactivate the fake again.
     */
    public static function deactivate(): void
    {
        self::$instance = null;
    }

    /**
     * Reset every recorded interaction but keep the fake active.
     */
    public function reset(): void
    {
        $this->queries = [];
        $this->responses = [];
        $this->insertId = 1;
        $this->uploadIsValid = true;
        $this->moveSucceeds = true;
        $this->accessAllowed = true;
        $this->readFiles = [];
        $this->headers = [];
        $this->referenceResults = [];
        $this->pregSplitFails = false;
    }

    /**
     * Next answer for is_new_reference().
     */
    public function isNewReference(): bool
    {
        if (empty($this->referenceResults)) {
            return true;
        }

        if (count($this->referenceResults) === 1) {
            return (bool) reset($this->referenceResults);
        }

        return (bool) array_shift($this->referenceResults);
    }

    /**
     * Queue a response that is used by the next unmatched query.
     *
     * @param array<int,array<string,mixed>>|callable|false $result
     */
    public function queue($result): void
    {
        $this->responses[] = ['pattern' => null, 'result' => $result, 'used' => false];
    }

    /**
     * Register a response bound to a SQL fragment.
     *
     * @param string $pattern
     * @param array<int,array<string,mixed>>|callable|false $result
     */
    public function on(string $pattern, $result): void
    {
        $this->responses[] = ['pattern' => $pattern, 'result' => $result, 'used' => false];
    }

    /**
     * The SQL of the last executed query, or an empty string.
     */
    public function lastSql(): string
    {
        if (empty($this->queries)) {
            return '';
        }

        $last = $this->queries[count($this->queries) - 1];

        return $last['sql'];
    }

    /**
     * Number of queries executed so far.
     */
    public function queryCount(): int
    {
        return count($this->queries);
    }

    /**
     * Execute a query against the fake.
     *
     * @param string $sql
     * @param mixed $params
     * @return FaDbResult|false
     */
    public function query(string $sql, $params = null)
    {
        $this->queries[] = ['sql' => $sql, 'params' => $params];

        $result = [];
        $found = false;

        foreach ($this->responses as $index => $response) {
            if ($response['used']) {
                continue;
            }
            if ($response['pattern'] !== null && stripos($sql, $response['pattern']) === false) {
                continue;
            }

            $this->responses[$index]['used'] = true;
            $result = $this->resolve($response['result'], $sql);
            $found = true;
            break;
        }

        if ($result === false) {
            return false;
        }

        // mysqli_query() only returns a result set for statements that produce
        // rows; everything else returns TRUE.
        if (preg_match('/^\s*(SELECT|SHOW)\b/i', $sql) === 1) {
            return $result instanceof FaDbResult ? $result : new FaDbResult(is_array($result) ? $result : []);
        }

        return true;
    }

    /**
     * Turn a registered response into a result handle.
     *
     * @param mixed $result
     * @return FaDbResult|false
     */
    private function resolve($result, string $sql)
    {
        if (is_callable($result)) {
            $result = $result($sql);
        }

        if ($result === false) {
            return false;
        }

        if ($result instanceof FaDbResult) {
            return $result;
        }

        return new FaDbResult(is_array($result) ? $result : []);
    }
}
