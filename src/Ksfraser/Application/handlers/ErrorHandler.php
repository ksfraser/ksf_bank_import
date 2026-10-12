<?php
 * Referenced in RTM (FR-001 through FR-012, ARCH-001 through ARCH-010, BR-001 through BR-012) */
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\Application\Handlers;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Ksfraser\Application\Config\Config;

//TODO: Convert HTML below into classes

class ErrorHandler
{
    private $logger;
    private $displayErrors;

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

    public function handleException(\Throwable $e): void
    {
        $this->logException($e);
        $this->displayError($e);
    }

    private function logException(\Throwable $e): void
    {
        $this->logger->error($e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    }

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
