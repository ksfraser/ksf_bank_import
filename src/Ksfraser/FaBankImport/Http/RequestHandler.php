<?php

namespace Ksfraser\FaBankImport\Http;

use Symfony\Component\HttpFoundation\Request;

class RequestHandler
{
    private $request;
    private $transactionCommand;
    private $params;
    private $middlewares = [];
    private $middlewareIndex = 0;

    public function __construct(array $params = [])
    {
        $this->request = Request::createFromGlobals();
        $this->params = $params;
        
        // Check for transaction in POST data first, then params
        if ($this->request->request->has('transaction')) {
            $this->transactionCommand = $this->request->request->get('transaction');
        } elseif (isset($params['transaction'])) {
            $this->transactionCommand = $params['transaction'];
        }
    }

    public function getRequest(): Request
    {
        return $this->request;
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
        return array_merge(
            $this->params,
            $this->request->query->all(),
            $this->request->request->all()
        );
    }

    public function isPost(): bool
    {
        return $this->request->isMethod('POST');
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