<?php

namespace Ksfraser\FaBankImport\Http;

class RequestHandler
{
    private $transactionCommand;
    private $params;
    private $middlewares = [];
    private $middlewareIndex = 0;

    public function __construct(array $params = [])
    {
        $this->params = $params;
        if (isset($params['transaction'])) {
            $this->transactionCommand = $params['transaction'];
        }
    }

    public function addMiddleware($middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function handle()
    {
        return $this->process();
    }

    public function process()
    {
        if ($this->middlewareIndex >= count($this->middlewares)) {
            // End of middleware chain, process the actual request
            return $this->processRequest();
        }

        $middleware = $this->middlewares[$this->middlewareIndex];
        $this->middlewareIndex++;

        return $middleware->process($this, function($request) {
            return $this->process();
        });
    }

    public function getTransactionCommand(): ?array
    {
        return $this->transactionCommand;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    private function processRequest()
    {
        // This would be implemented by specific request handlers
        // For now, we'll return a basic success response
        return [
            'success' => true,
            'message' => 'Request processed successfully'
        ];
    }
}