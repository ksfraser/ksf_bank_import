<?php

namespace Ksfraser\Application\Handlers;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Ksfraser\Application\Config\Config;

//TODO: Convert HTML below into classes

/**
 * @BABOK FR-LOG-002, FR-LOG-005 | BR-LOG-001 | UAT-LOG-001
 */
class ErrorHandler
{
    private $logger;
    private $displayErrors;

    /**
 * @BABOK FR-LOG-002, FR-LOG-005 | BR-LOG-001 | UAT-LOG-001
     */
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

    /**
 * @BABOK FR-LOG-002, FR-LOG-005 | BR-LOG-001 | UAT-LOG-001
     */
    public function handleException(\Throwable $e): void
    {
        $this->logException($e);
        $this->displayError($e);
    }

    /**
 * @BABOK FR-LOG-002, FR-LOG-005 | BR-LOG-001 | UAT-LOG-001
     */
    private function logException(\Throwable $e): void
    {
        $this->logger->error($e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    }

    /**
 * @BABOK FR-LOG-002, FR-LOG-005 | BR-LOG-001 | UAT-LOG-001
     */
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
