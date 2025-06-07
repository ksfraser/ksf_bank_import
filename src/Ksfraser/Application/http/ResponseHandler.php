<?php

namespace Ksfraser\Application\Http;

//TODO: Determine if this comes from a framework I could include instead!

class ResponseHandler
{
    private $headers = [];
    private $content = '';
    private $statusCode = 200;

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function redirect(string $url, int $statusCode = 302): void
    {
        $this->setHeader('Location', $url);
        $this->setStatusCode($statusCode);
        $this->send();
    }

    public function json(array $data): void
    {
        $this->setHeader('Content-Type', 'application/json');
        $this->setContent(json_encode($data));
        $this->send();
    }

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
