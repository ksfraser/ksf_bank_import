<?php
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Http;

/**
 * Request handler wrapping the local Http\Request value object.
 *
 * Decoupled from symfony/http-foundation (#44 option 2).
 *
 * @since 20260822
 */
class RequestHandler
{
    /** @var Request */
    private $request;

    /** @var array|null */
    private $transactionCommand;

    /** @var array */
    private $params;

    /** @var array */
    private $middlewares = [];

    /** @var int */
    private $middlewareIndex = 0;

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

    /**
     * Current request value object.
     *
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Append a middleware to the pipeline.
     *
     * @param object $middleware Middleware with process() method.
     * @return self
     */
    public function addMiddleware($middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Run the middleware pipeline.
     *
     * @return mixed
     */
    public function handle()
    {
        return $this->process();
    }

    /**
     * Run the middleware pipeline.
     *
     * @return mixed
     */
    public function process()
    {
        if ($this->middlewareIndex >= count($this->middlewares)) {
            return $this->processRequest();
        }

        $middleware = $this->middlewares[$this->middlewareIndex];
        $this->middlewareIndex++;

        return $middleware->process($this, function ($request) {
            return $this->process();
        });
    }

    /**
     * Transaction command from POST or params.
     *
     * @return array|null
     */
    public function getTransactionCommand(): ?array
    {
        return $this->transactionCommand;
    }

    /**
     * Merged params: explicit + query + post.
     *
     * @return array
     */
    public function getParams(): array
    {
        return array_merge(
            $this->params,
            $this->request->getQueryParams(),
            $this->request->getPostParams()
        );
    }

    /**
     * Whether this is a POST request.
     *
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->request->isMethod('POST');
    }

    /**
     * Terminal request processing.
     *
     * @return array
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
