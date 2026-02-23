
namespace Ksfraser\FaBankImport\Service;

/**
 * BankImportLogger: Centralized error and diagnostic logging for the bank import module.
 * Extends BaseLogger for SRP-compliant, extensible logging.
 */
final class BankImportLogger extends BaseLogger
{
    public function __construct()
    {
        $logDir = null;
        $logFile = null;
        try {
            if (class_exists('Ksfraser\\FaBankImport\\Service\\BankImportPathResolver')) {
                $logDir = BankImportPathResolver::forCurrentCompany()->logsDir();
                if (!is_dir($logDir)) {
                    @mkdir($logDir, 0777, true);
                }
                $logFile = $logDir . DIRECTORY_SEPARATOR . 'error_log_' . date('ymd') . '.log';
            }
        } catch (\Throwable $e) {
            $logFile = null;
        }
        if (!$logFile) {
            $logFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bank_import_fallback.log';
        }
        parent::__construct('bank-import', $logFile, \Monolog\Logger::ERROR);
    }

    /**
     * Log an error message with optional context.
     * @param string $msg
     * @param array $context
     */
    public function logError(string $msg, array $context = []): void
    {
        $this->error($msg, $context);
        if (php_sapi_name() === 'cli') {
            echo $msg . "\n";
        }
    }
}
