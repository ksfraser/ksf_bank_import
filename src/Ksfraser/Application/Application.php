<?php

namespace Ksfraser\Application;

use Ksfraser\Application\Http\RequestHandler;
use Ksfraser\Application\Http\ResponseHandler;
use Ksfraser\Application\Middleware\MiddlewarePipeline;
use Ksfraser\Application\Middleware\AuthMiddleware;
use Ksfraser\Application\Middleware\TransactionValidationMiddleware;
use Ksfraser\Application\Middleware\PerformanceMonitoringMiddleware;
use Ksfraser\Application\Handlers\ErrorHandler;

class Application
{
    private $container;
    private $pipeline;
    private $errorHandler;

    public function __construct()
    {
        $this->container = Container::getInstance();
        $this->pipeline = new MiddlewarePipeline();
        $this->errorHandler = new ErrorHandler();
        
        $this->setupMiddleware();
        $this->setupErrorHandling();
    }

    private function setupMiddleware(): void
    {
        $this->pipeline
            ->pipe(new PerformanceMonitoringMiddleware()) // Add first to measure everything
            ->pipe(new AuthMiddleware())
            ->pipe(new TransactionValidationMiddleware());
    }

    private function setupErrorHandling(): void
    {
        set_exception_handler([$this->errorHandler, 'handleException']);
    }

    public function run(): void
    {
        try {
            $request = new RequestHandler();
            $controller = $this->container->getBankImportController();
            
            $action = $request->isPost() ? 'process' : 'index';
            
            $this->pipeline->process($request, function() use ($controller, $action) {
                return $controller->handle($action);
            });
        } catch (\Throwable $e) {
            $this->errorHandler->handleException($e);
        }
    }
}
