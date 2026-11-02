<?php
// @BABOK Referenced in RTM (FR-001 through FR-012, ARCH-001 through ARCH-010, BR-001 through BR-012) */
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\Application\Http;

//TODO: Determine if this comes from a framework I could include instead!

/**
 * @BABOK FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
 */
class ResponseHandler
{
    private $headers = [];
    private $content = '';
    private $statusCode = 200;

    /**
 * @BABOK FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
 * @BABOK FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
     */
    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
 * @BABOK FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
 * @BABOK FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
     */
    public function redirect(string $url, int $statusCode = 302): void
    {
        $this->setHeader('Location', $url);
        $this->setStatusCode($statusCode);
        $this->send();
    }

    /**
 * @BABOK FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
     */
    public function json(array $data): void
    {
        $this->setHeader('Content-Type', 'application/json');
        $this->setContent(json_encode($data));
        $this->send();
    }

    /**
 * @BABOK FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
     */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);
            foreach ($this->headers as $name => $value) {
                header("$name: $value");
            }
        }
        echo $this->content;
        exit;
    }
}
