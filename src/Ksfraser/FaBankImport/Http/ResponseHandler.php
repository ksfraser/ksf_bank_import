<?php

namespace Ksfraser\FaBankImport\Http;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ResponseHandler
{
    private $response;

    public function __construct()
    {
        $this->response = new Response();
    }

    public function setHeader(string $name, string $value): self
    {
        $this->response->headers->set($name, $value);
        return $this;
    }

    public function setStatusCode(int $code): self
    {
        $this->response->setStatusCode($code);
        return $this;
    }

    public function setContent(string $content): self
    {
        $this->response->setContent($content);
        return $this;
    }

    public function redirect(string $url, int $statusCode = 302): void
    {
        $response = new RedirectResponse($url, $statusCode);
        $response->send();
    }

    public function json(array $data): void
    {
        $response = new JsonResponse($data);
        $response->send();
    }

    public function send(): void
    {
        $this->response->send();
    }

    public function getResponse(): Response
    {
        return $this->response;
    }
}