<?php

namespace Ksfraser\FaBankImport\Http;

use Symfony\Component\HttpFoundation\Request;

/**
 * @BABOK CLASS ROLLUP: RequestHandler — 9 methods; FR-001 (9); IR-001 (9)
 * @BABOK RTM: FR-001 / IR-001; file: Http/RequestHandler.php; coverage: docs/TestCases/ (file rollup)
 */
class RequestHandler
{
    private $request;
    private $transactionCommand;
    private $params;
    private $middlewares = [];
    private $middlewareIndex = 0;

    /** @BABOK FR-001 / IR-001 — method: __construct */
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

    /** @BABOK FR-001 / IR-001 — method: getRequest */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /** @BABOK FR-001 / IR-001 — method: addMiddleware */
    public function addMiddleware($middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /** @BABOK FR-001 / IR-001 — method: handle */
    public function handle()
    {
        return $this->process();
    }

    /** @BABOK FR-001 / IR-001 — method: process */
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

    /** @BABOK FR-001 / IR-001 — method: getTransactionCommand */
    public function getTransactionCommand(): ?array
    {
        return $this->transactionCommand;
    }

    /** @BABOK FR-001 / IR-001 — method: getParams */
    public function getParams(): array
    {
        return array_merge(
            $this->params,
            $this->request->query->all(),
            $this->request->request->all()
        );
    }

    /** @BABOK FR-001 / IR-001 — method: isPost */
    public function isPost(): bool
    {
        return $this->request->isMethod('POST');
    }

    /** @BABOK FR-001 / IR-001 — method: processRequest */
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