<?php

namespace Ksfraser\FaBankImport\Http;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @BABOK RTM: FR-001 / IR-001; file: Http/ResponseHandler.php; coverage: docs/TestCases/ (file rollup)
 */
class ResponseHandler
{
    private $response;

    /** @BABOK FR-001 / IR-001 — method: __construct */
    public function __construct()
    {
        $this->response = new Response();
    }

    /** @BABOK FR-001 / IR-001 — method: setHeader */
    public function setHeader(string $name, string $value): self
    {
        $this->response->headers->set($name, $value);
        return $this;
    }

    /** @BABOK FR-001 / IR-001 — method: setStatusCode */
    public function setStatusCode(int $code): self
    {
        $this->response->setStatusCode($code);
        return $this;
    }

    /** @BABOK FR-001 / IR-001 — method: setContent */
    public function setContent(string $content): self
    {
        $this->response->setContent($content);
        return $this;
    }

    /** @BABOK FR-001 / IR-001 — method: redirect */
    public function redirect(string $url, int $statusCode = 302): void
    {
        $response = new RedirectResponse($url, $statusCode);
        $response->send();
    }

    /** @BABOK FR-001 / IR-001 — method: json */
    public function json(array $data): void
    {
        $response = new JsonResponse($data);
        $response->send();
    }

    /** @BABOK FR-001 / IR-001 — method: send */
    public function send(): void
    {
        $this->response->send();
    }

    /** @BABOK FR-001 / IR-001 — method: getResponse */
    public function getResponse(): Response
    {
        return $this->response;
    }
}