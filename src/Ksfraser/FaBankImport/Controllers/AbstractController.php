<?php

/** @BABOK RTM: ARCH-004 / FR-014 / ARCH-001*/
namespace Ksfraser\FaBankImport\Controllers;

use Ksfraser\FaBankImport\Http\RequestHandler;
use Ksfraser\FaBankImport\Http\ResponseHandler;
use Ksfraser\FaBankImport\Middleware\MiddlewarePipeline;

/**
 * @BABOK CLASS ROLLUP: AbstractController — 6 methods; BR-002 (6); FR-008 (6); FR-018 (6)
 * @BABOK RTM: BR-002 / FR-008 / FR-018; file: Controllers/AbstractController.php; coverage: docs/TestCases/ (file rollup)
 */
abstract class AbstractController
{
    protected $request;
    protected $response;
    protected $pipeline;

    /** @BABOK BR-002 / FR-008 / FR-018 — method: __construct */
    public function __construct()
    {
        $this->request = new RequestHandler();
        $this->response = new ResponseHandler();
        $this->pipeline = new MiddlewarePipeline();
        $this->initializeMiddleware();
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: initializeMiddleware */
    protected function initializeMiddleware(): void
    {
        // Override in child controllers to add middleware
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: render */
    protected function render(string $view, array $data = []): void
    {
        extract($data);
        ob_start();
        include __DIR__ . "/../../Views/$view.php";
        $content = ob_get_clean();
        
        $this->response->setContent($content)->send();
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: json */
    protected function json(array $data): void
    {
        $this->response->json($data);
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: redirect */
    protected function redirect(string $url): void
    {
        $this->response->redirect($url);
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: handle */
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