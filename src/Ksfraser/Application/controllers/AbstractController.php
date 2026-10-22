<?php

namespace Ksfraser\Application\Controllers;

use Ksfraser\Application\Http\RequestHandler;
use Ksfraser\Application\Http\ResponseHandler;
use Ksfraser\Application\Middleware\MiddlewarePipeline;

/**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
 */
abstract class AbstractController
{
    protected $request;
    protected $response;
    protected $pipeline;

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    public function __construct()
    {
        $this->request = new RequestHandler();
        $this->response = new ResponseHandler();
        $this->pipeline = new MiddlewarePipeline();
        $this->initializeMiddleware();
    }

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    protected function initializeMiddleware(): void
    {
        // Override in child controllers to add middleware
    }

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    protected function render(string $view, array $data = []): void
    {
        extract($data);
        ob_start();
        include __DIR__ . "/../../views/$view.php";
        $content = ob_get_clean();
        
        $this->response->setContent($content)->send();
    }

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    protected function json(array $data): void
    {
        $this->response->json($data);
    }

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    protected function redirect(string $url): void
    {
        $this->response->redirect($url);
    }

//TODO: Figure out what this is actually doing.
    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    public function handle(string $action, array $params = [])
    {
        return $this->pipeline->process(
            $this->request,
            function () use ($action, $params) {
                if (!method_exists($this, $action)) {
                    throw new \RuntimeException("Action '$action' not found");
                }
                return $this->$action(...$params);
            }
        );
    }
}
