<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :ErrorLogger [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for ErrorLogger.
 */

namespace Ksfraser\FaBankImport\Handlers;

use Ksfraser\FaBankImport\Config\Config;
use Ksfraser\FaBankImport\Services\BaseLogger;

class ErrorLogger extends BaseLogger
{
    private $displayErrors;

    public function __construct()
    {
        $config = Config::getInstance();
        $this->displayErrors = $config->get('app.debug', false);
        $logFile = $config->get('logging.path') . '/error.log';
        parent::__construct('bank-import', $logFile, \Monolog\Logger::WARNING);
    }

    public function logException(\Throwable $e): void
    {
        $this->error($e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    }

    public function displayError(\Throwable $e): void
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

