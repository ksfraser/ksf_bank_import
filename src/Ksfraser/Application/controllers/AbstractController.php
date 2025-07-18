<?php

namespace Ksfraser\Application\Controllers;

use Ksfraser\Application\Http\RequestHandler;
use Ksfraser\Application\Http\ResponseHandler;
use Ksfraser\Application\Middleware\MiddlewarePipeline;

abstract class AbstractController
{
    protected $request;
    protected $response;
    protected $pipeline;

    public function __construct()
    {
        $this->request = new RequestHandler();
        $this->response = new ResponseHandler();
        $this->pipeline = new MiddlewarePipeline();
        $this->initializeMiddleware();
    }

    protected function initializeMiddleware(): void
    {
        // Override in child controllers to add middleware
    }

    protected function render(string $view, array $data = []): void
    {
        extract($data);
        ob_start();
        include __DIR__ . "/../../views/$view.php";
        $content = ob_get_clean();
        
        $this->response->setContent($content)->send();
    }

    protected function json(array $data): void
    {
        $this->response->json($data);
    }

    protected function redirect(string $url): void
    {
        $this->response->redirect($url);
    }

//TODO: Figure out what this is actually doing.
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
