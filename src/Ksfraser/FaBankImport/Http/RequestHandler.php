<?php

namespace Ksfraser\FaBankImport\Http;

/**
 * @BABOK FR-IMP-001, FR-PROC-011 | BR-IMP-001 | UAT-IMP-001

 *
 * Uses the module's own Request value object (#44); symfony/http-foundation
 * is no longer required for the request side.
 */
class RequestHandler
{
    private $request;
    private $transactionCommand;
    private $params;
    private $middlewares = [];
    private $middlewareIndex = 0;

    /** * @BABOK FR-IMP-001, FR-PROC-011 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-001, FR-PROC-011 | BR-IMP-001 | UAT-IMP-001
*/
    public function __construct(array $params = [])
    {
        $this->request = Request::createFromGlobals();
        $this->params = $params;

        // Check for transaction in POST data first, then params
        if ($this->request->hasPost('transaction')) {
            $this->transactionCommand = $this->request->getPost('transaction');
        } elseif (isset($params['transaction'])) {
            $this->transactionCommand = $params['transaction'];
        }
    }

    /** * @BABOK FR-IMP-001, FR-PROC-011 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-001, FR-PROC-011 | BR-IMP-001 | UAT-IMP-001
*/
    public function getRequest(): Request
    {
        return $this->request;
    }

    /** * @BABOK FR-IMP-001, FR-PROC-011 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-001, FR-PROC-011 | BR-IMP-001 | UAT-IMP-001
*/
    public function getQuery(string $key, $default = null)
    {
        return $this->request->getQueryParams()[$key] ?? $default;
    }

    /** * @BABOK FR-IMP-001, FR-PROC-011 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-001, FR-PROC-011 | BR-IMP-001 | UAT-IMP-001
*/
    public function addMiddleware($middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /** * @BABOK FR-IMP-001, FR-PROC-011 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-001, FR-PROC-011 | BR-IMP-001 | UAT-IMP-001
*/
    public function handle()
    {
        return $this->process();
    }

    /** * @BABOK FR-IMP-001, FR-PROC-011 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-001, FR-PROC-011 | BR-IMP-001 | UAT-IMP-001
*/
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

    /** * @BABOK FR-IMP-001, FR-PROC-011 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-001, FR-PROC-011 | BR-IMP-001 | UAT-IMP-001
*/
    public function getTransactionCommand(): ?array
    {
        return $this->transactionCommand;
    }

    /** * @BABOK FR-IMP-001, FR-PROC-011 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-001, FR-PROC-011 | BR-IMP-001 | UAT-IMP-001
*/
    public function getParams(): array
    {
        return array_merge(
            $this->params,
            $this->request->getQueryParams(),
            $this->request->getPostParams()
        );
    }

    /** * @BABOK FR-IMP-001, FR-PROC-011 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-001, FR-PROC-011 | BR-IMP-001 | UAT-IMP-001
*/
    public function isPost(): bool
    {
        return $this->request->isMethod('POST');
    }

    /** * @BABOK FR-IMP-001, FR-PROC-011 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-001, FR-PROC-011 | BR-IMP-001 | UAT-IMP-001
*/
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