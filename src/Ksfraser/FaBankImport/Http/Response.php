<?php
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Http;

/**
 * Minimal HTTP response value object.
 *
 * Replaces the symfony/http-foundation dependency (#44): the module only
 * requires status + headers + content semantics. Immutable; fluent setters.
 *
 * @since 20260822
 * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
 */
class Response
{
    /** @var int */
    protected $statusCode;

    /** @var array<string, string> */
    protected $headers;

    /** @var string */
    protected $content;

    /**
     * Constructor.
     *
     * @param string $content    Body content.
     * @param int    $statusCode HTTP status code.
     * @param array<string, string> $headers Headers.
 * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
     */
    public function __construct(
        string $content = '',
        int $statusCode = 200,
        array $headers = array()
    ) {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * Clone with new body content.
     *
     * @param string $content Content.
     * @return self
 * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
     */
    public function withContent(string $content): self
    {
        $clone = clone $this;
        $clone->content = $content;
        return $clone;
    }

    /**
     * Clone with new status code.
     *
     * @param int $code HTTP status.
     * @return self
 * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
     */
    public function withStatusCode(int $code): self
    {
        $clone = clone $this;
        $clone->statusCode = $code;
        return $clone;
    }

    /**
     * Clone with a header set.
     *
     * @param string $name  Header name.
     * @param string $value Header value.
     * @return self
 * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
     */
    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;
        return $clone;
    }

    /**
     * Create a JSON response.
     *
     * @param array $data   Payload.
     * @param int   $status HTTP status.
     * @return self
 * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
     */
    public static function json(array $data, int $status = 200): self
    {
        return (new self('', $status))
            ->withHeader('Content-Type', 'application/json')
            ->withContent((string) json_encode($data));
    }

    /**
     * Create a redirect response.
     *
     * @param string $url    Target URL.
     * @param int    $status Redirect status (default 302).
     * @return self
 * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
     */
    public static function redirect(string $url, int $status = 302): self
    {
        return (new self('', $status))
            ->withHeader('Location', $url);
    }

    /**
     * Status code.
     *
     * @return int
 * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Body content.
     *
     * @return string
 * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * All headers.
     *
     * @return array<string, string>
 * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Emit status line, headers and body.
     *
     * Headers are only emitted when output has not started yet (unit tests
     * and CLI capture the body without SAPI headers).
     *
     * @return void
 * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
     */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);
            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }
        echo $this->content;
    }
}
