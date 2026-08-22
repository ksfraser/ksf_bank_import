<?php
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Models;

/**
 * FA-backed bank account details provider.
 *
 * Wraps the legacy ksf_modules_common \fa_bank_accounts class. The legacy
 * require is guarded so this provider can only be instantiated in a real
 * FrontAccounting + module-common environment; use the interface elsewhere.
 *
 * @since 20260822
 */
class FaBankAccountsProvider implements BankAccountDetailsProviderInterface
{
    /** @var bool */
    protected static $legacyAvailable;

    /**
     * Whether the legacy \fa_bank_accounts dependency is loadable here.
     *
     * @return bool
     */
    public static function legacyAvailable(): bool
    {
        if (static::$legacyAvailable === null) {
            $path = __DIR__ . '/../../../../ksf_modules_common/class.fa_bank_accounts.php';
            // The package sits beside the module directory, not inside it.
            if (!file_exists($path)) {
                $alt = __DIR__ . '/../../../../../ksf_modules_common/class.fa_bank_accounts.php';
                $path = file_exists($alt) ? $alt : $path;
            }
            static::$legacyAvailable = file_exists($path) && !defined('KSF_TEST_COMPAT');
        }
        return static::$legacyAvailable;
    }

    /**
     * Load the legacy class when possible.
     *
     * @return void
     * @throws \RuntimeException When unavailable (compat mode / clean checkout).
     */
    protected function ensureLegacyLoaded(): void
    {
        if (!class_exists('fa_bank_accounts', false)) {
            if (!static::legacyAvailable()) {
                throw new \RuntimeException(
                    'FaBankAccountsProvider requires ksf_modules_common (unavailable under KSF_TEST_COMPAT). '
                    . 'Inject a BankAccountDetailsProviderInterface instead.'
                );
            }
            require_once static::legacyPath();
        }
    }

    /**
     * Resolved path of the legacy class file.
     *
     * @return string
     */
    protected static function legacyPath(): string
    {
        $path = __DIR__ . '/../../../../ksf_modules_common/class.fa_bank_accounts.php';
        $alt = __DIR__ . '/../../../../../ksf_modules_common/class.fa_bank_accounts.php';
        return file_exists($alt) ? $alt : $path;
    }

    /**
     * @inheritDoc
     */
    public function getByNumber(string $accountNumber): ?array
    {
        $this->ensureLegacyLoaded();

        // Legacy constructor expects a caller exposing our_account via magic get.
        $probe = new class($accountNumber) {
            /** @var string */
            public $our_account;
            public function __construct(string $n)
            {
                $this->our_account = $n;
            }
        };
        $accounts = new \fa_bank_accounts($probe);
        return $accounts->getByBankAccountNumber($accountNumber);
    }
}
