<?php

declare(strict_types=1);

/**
 * Scenario: exercises the FrontAccounting aware branch of
 * \Ksfraser\FaBankImport\Config\BankImportConfig.
 *
 * The class checks for the global db_escape() function to decide whether it
 * detects a live FrontAccounting runtime by the global $db handle that FA's
 * connect_db.inc sets. The FA mocks of vendor/ksfraser/famock provide
 * db_escape() but never a $db handle, so this scenario runs with no $db and
 * exercises the "not in FrontAccounting" branch.
 */

use Ksfraser\FaBankImport\Config\BankImportConfig;

if (!function_exists('get_company_pref')) {
    /**
     * @param string $name    Preference name.
     * @param string $default Default value.
     * @return string
     */
    function get_company_pref($name, $default = '')
    {
        return $GLOBALS['__company_prefs'][$name] ?? $default;
    }
}

if (!function_exists('set_company_pref')) {
    /**
     * @param string $name  Preference name.
     * @param string $value Preference value.
     * @return void
     */
    function set_company_pref($name, $value)
    {
        $GLOBALS['__company_prefs'][$name] = $value;
    }
}

$GLOBALS['__company_prefs'] = [];

echo 'IS_FA_RUNTIME=' . (isset($GLOBALS['db']) ? '1' : '0') . "\n";

BankImportConfig::setTransRefAccount('1234');
echo 'ACCOUNT=' . BankImportConfig::getTransRefAccount() . "\n";

BankImportConfig::setTransRefLoggingEnabled(false);
echo 'LOGGING_ENABLED=' . (BankImportConfig::getTransRefLoggingEnabled() ? '1' : '0') . "\n";

echo 'ALL_SETTINGS=' . json_encode(BankImportConfig::getAllSettings()) . "\n";
echo "BANK_IMPORT_CONFIG_COMPLETED\n";
