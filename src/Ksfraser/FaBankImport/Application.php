<?php

namespace Ksfraser\FaBankImport;

use Ksfraser\FaBankImport\Http\RequestHandler;
use Ksfraser\FaBankImport\Http\ResponseHandler;
use Ksfraser\FaBankImport\Middleware\MiddlewarePipeline;
use Ksfraser\FaBankImport\Middleware\AuthMiddleware;
use Ksfraser\FaBankImport\Middleware\TransactionValidationMiddleware;
use Ksfraser\FaBankImport\Middleware\PerformanceMonitoringMiddleware;
use Ksfraser\FaBankImport\Handlers\ErrorHandler;

/**
 * @BABOK RTM: BR-002 / FR-008 / FR-018; file: Application.php; coverage: docs/TestCases/ (file rollup)
 */
class Application
{
    private $container;
    private $pipeline;
    private $errorHandler;

    /** @BABOK BR-002 / FR-008 / FR-018 — method: __construct */
    public function __construct()
    {
        $this->container = Container::getInstance();
        $this->pipeline = new MiddlewarePipeline();
        $this->errorHandler = new ErrorHandler();
        
        $this->setupMiddleware();
        $this->setupErrorHandling();
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: setupMiddleware */
    private function setupMiddleware(): void
    {
        $this->pipeline
            ->pipe(new PerformanceMonitoringMiddleware()) // Add first to measure everything
            ->pipe(new AuthMiddleware())
            ->pipe(new TransactionValidationMiddleware());
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: setupErrorHandling */
    private function setupErrorHandling(): void
    {
        set_exception_handler([$this->errorHandler, 'handleException']);
    }

    /** @BABOK BR-002 / FR-008 / FR-018 — method: run */
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