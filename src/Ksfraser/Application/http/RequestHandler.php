<?php

namespace Ksfraser\Application\Http;

//TODO: Determine if this comes from a framework I could include instead.

/**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 */
class RequestHandler
{
    private $transactionCommand;
    private $params;
    private $middlewares = [];
    private $middlewareIndex = 0;

    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function __construct(array $params = [])
    {
        $this->params = $params;
        if (isset($params['transaction'])) {
            $this->transactionCommand = $params['transaction'];
        }
    }

    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function addMiddleware($middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function handle()
    {
        return $this->process();
    }

    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
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

    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getTransactionCommand(): ?array
    {
        return $this->transactionCommand;
    }

    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Whether the current request is a POST.
     *
     * Application::run() uses this to pick between the list and the process
     * action, so the generic shell needs it just as much as the module shell.
     *
     * @return bool
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function isPost(): bool
    {
        return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST';
    }

    /**
     * A single query-string parameter, with a default.
     *
     * The admin screens read their date range / output format from the query
     * string, e.g. AdminController::performanceReport() asks for
     * getQuery('start_date', $default).
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getQuery(string $key, $default = null)
    {
        return array_key_exists($key, $_GET) ? $_GET[$key] : $default;
    }


    /**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
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
