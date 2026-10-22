<?php

namespace Ksfraser\FaBankImport\Controllers;

use Ksfraser\FaBankImport\Http\RequestHandler;
use Ksfraser\FaBankImport\Http\ResponseHandler;
use Ksfraser\FaBankImport\Middleware\MiddlewarePipeline;

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
        // Views live in the module-root views/ directory; support both the
        // repo checkout layout and an installed modules/<name> layout.
        $candidates = [
            __DIR__ . "/../../views/$view.php",      // src/Ksfraser/FaBankImport/controllers -> repo views (legacy)
            __DIR__ . "/../../../../views/$view.php", // -> module root views/
        ];
        $template = null;
        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                $template = $candidate;
                break;
            }
        }
        if ($template === null) {
            throw new \RuntimeException("View not found: {$view}");
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