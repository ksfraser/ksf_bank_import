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
        /*
         * The views live in the module's views/ directory. The shell itself
         * sits in src/Ksfraser/Application, so it has to look for them in the
         * module root (and in the migrated view tree).
         */
        $candidates = [
            __DIR__ . '/../../../../views/' . $view . '.php',        // -> module root views/
            __DIR__ . '/../../FaBankImport/Views/' . $view . '.php', // -> the migrated view tree
        ];
        $template = null;
        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                $template = $candidate;
                break;
            }
        }
        if ($template === null) {
            throw new \RuntimeException('View not found: ' . $view);
        }

        ob_start();
        include $template;
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
