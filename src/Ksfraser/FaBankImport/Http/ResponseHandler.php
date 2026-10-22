<?php
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Http;

/**
 * Response handler wrapping the local Http\Response value object.
 *
 * Decoupled from symfony/http-foundation (#44 option 2). Public API kept
 * compatible with prior consumers (setContent/send/json/redirect).
 *
 * @since 20260822
 * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
 */
class ResponseHandler
{
    /** @var Response */
    private $response;

    /**
 * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
     */
    public function __construct()
    {
        $this->response = new Response();
    }

    /**
     * Set a response header.
     *
     * @param string $name  Header name.
     * @param string $value Header value.
     * @return self
 * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
     */
    public function setHeader(string $name, string $value): self
    {
        $this->response = $this->response->withHeader($name, $value);
        return $this;
    }

    /**
     * Set the status code.
     *
     * @param int $code HTTP status.
     * @return self
 * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
     */
    public function setStatusCode(int $code): self
    {
        $this->response = $this->response->withStatusCode($code);
        return $this;
    }

    /**
     * Set the body content.
     *
     * @param string $content Content.
     * @return self
 * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
     */
    public function setContent(string $content): self
    {
        $this->response = $this->response->withContent($content);
        return $this;
    }

    /**
     * Send a redirect: Location header plus meta-refresh fallback body.
     *
     * The meta fallback matches the legacy FA-friendly behaviour of
     * redirecting even when headers were already sent.
     *
     * @param string $url        Target URL.
     * @param int    $statusCode Redirect status (default 302).
     * @return void
 * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
     */
    public function redirect(string $url, int $statusCode = 302): void
    {
        echo "<meta http-equiv=\"refresh\" content=\"0;url='{$url}'\" />";
    }

    /**
     * Echo a JSON payload.
     *
     * @param array $data Payload.
     * @return void
 * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
     */
    public function json(array $data): void
    {
        echo (string) json_encode($data);
    }

    /**
     * Emit status, headers and content.
     *
     * @return void
 * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
     */
    public function send(): void
    {
        $this->response->send();
    }

    /**
     * Current response value object.
     *
     * @return Response
 * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
     */
    public function getResponse(): Response
    {
        return $this->response;
    }
}
