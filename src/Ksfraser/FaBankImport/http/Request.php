<?php
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Http;

/**
 * Minimal HTTP request value object.
 *
 * Replaces the symfony/http-foundation dependency (#44 option 2): captures
 * query/post params and the request method.
 *
 * @since 20260822
 */
class Request
{
    /** @var array */
    protected $queryParams;

    /** @var array */
    protected $postParams;

    /** @var string */
    protected $method;

    /**
     * Build from PHP superglobals.
     *
     * @return self
     */
    public static function createFromGlobals(): self
    {
        return new self(
            $_GET ?? array(),
            $_POST ?? array(),
            $_SERVER['REQUEST_METHOD'] ?? 'GET'
        );
    }

    /**
     * Constructor.
     *
     * @param array  $queryParams $_GET equivalent.
     * @param array  $postParams  $_POST equivalent.
     * @param string $method      HTTP method.
     */
    public function __construct(
        array $queryParams = array(),
        array $postParams = array(),
        string $method = 'GET'
    ) {
        $this->queryParams = $queryParams;
        $this->postParams = $postParams;
        $this->method = strtoupper($method);
    }

    /**
     * Query (GET) params.
     *
     * @return array
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * Post params.
     *
     * @return array
     */
    public function getPostParams(): array
    {
        return $this->postParams;
    }

    /**
     * Whether a POST param exists.
     *
     * @param string $key Param name.
     * @return bool
     */
    public function hasPost(string $key): bool
    {
        return array_key_exists($key, $this->postParams);
    }

    /**
     * Fetch a POST param or null.
     *
     * @param string $key Param name.
     * @return mixed|null
     */
    public function getPost(string $key)
    {
        return $this->postParams[$key] ?? null;
    }

    /**
     * Compare the HTTP method (case-insensitive).
     *
     * @param string $method Method to compare.
     * @return bool
     */
    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }
}
