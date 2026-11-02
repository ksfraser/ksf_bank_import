<?php

namespace Ksfraser\FaBankImport\Handlers;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Ksfraser\FaBankImport\Config\Config;

/**
 * @BABOK CLASS ROLLUP: ErrorHandler — 4 methods; FR-049 (4)
 * @BABOK RTM: FR-049 / ARCH-004; file: Handlers/ErrorHandler.php; coverage: docs/TestCases/ (file rollup)
 */
class ErrorHandler
{
    private $logger;
    private $displayErrors;

    /** @BABOK FR-049 / ARCH-004 — method: __construct */
    public function __construct()
    {
        $config = Config::getInstance();
        $this->displayErrors = $config->get('app.debug', false);
        
        $this->logger = new Logger('bank-import');
        $this->logger->pushHandler(
            new StreamHandler(
                $config->get('logging.path') . '/error.log',
                Logger::WARNING
            )
        );
    }

    /** @BABOK FR-049 / ARCH-004 — method: handleException */
    public function handleException(\Throwable $e): void
    {
        $this->logException($e);
        $this->displayError($e);
    }

    /** @BABOK FR-049 / ARCH-004 — method: logException */
    private function logException(\Throwable $e): void
    {
        $this->logger->error($e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    }

    /** @BABOK FR-049 / ARCH-004 — method: displayError */
    private function displayError(\Throwable $e): void
    {
        if ($this->displayErrors) {
            echo sprintf(
                "<div class='error'><h2>Error</h2><p>%s</p><pre>%s</pre></div>",
                htmlspecialchars($e->getMessage()),
                htmlspecialchars($e->getTraceAsString())
            );
        } else {
            echo "<div class='error'>An error occurred. Please try again or contact support.</div>";
        }
    }
}