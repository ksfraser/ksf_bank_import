<?php

namespace Ksfraser\FaBankImport\Http;

/**
 * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001

 *
 * Uses the module's own Response value object (#44); symfony/http-foundation
 * is no longer required for the response side.
 */
class ResponseHandler
{
    private $response;

    /** * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
*/
    public function __construct()
    {
        $this->response = new Response();
    }

    /** * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
*/
    public function setHeader(string $name, string $value): self
    {
        $this->response = $this->response->withHeader($name, $value);
        return $this;
    }

    /** * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
*/
    public function setStatusCode(int $code): self
    {
        $this->response = $this->response->withStatusCode($code);
        return $this;
    }

    /** * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
*/
    public function setContent(string $content): self
    {
        $this->response = $this->response->withContent($content);
        return $this;
    }

    /** * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
*/
    public function redirect(string $url, int $statusCode = 302): void
    {
        // By the time a redirect is issued the page has typically rendered
        // already, so headers are sent and only the meta refresh fallback in
        // the body still navigates the client. The internal response object
        // is left untouched.
        echo "<meta http-equiv=\"refresh\" content=\"0;url='{$url}'\" />";
    }

    /** * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
*/
    public function json(array $data): void
    {
        echo (string) json_encode($data);
    }

    /** * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
*/
    public function send(): void
    {
        $this->response->send();
    }

    /** * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-010, FR-PROC-010 | BR-IMP-001 | UAT-IMP-001
*/
    public function getResponse(): Response
    {
        return $this->response;
    }
}