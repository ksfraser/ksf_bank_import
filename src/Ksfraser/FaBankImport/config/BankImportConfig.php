<?php

/**
 * Bank Import Configuration
 *
 * Centralized configuration management for Bank Import module.
 * Uses FrontAccounting's company preferences system.
 *
 * @package    Ksfraser\FaBankImport\Config
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251021
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Config;

/**
 * Bank Import Configuration
 *
 * Provides type-safe access to module configuration settings.
 * All settings are stored in FrontAccounting's company preferences table.
 */
class BankImportConfig
{
    /**
     * Configuration key for transaction reference logging enabled/disabled
     */
    private const KEY_TRANS_REF_LOGGING = 'bank_import_trans_ref_logging';

    /**
     * Configuration key for transaction reference GL account
     */
    private const KEY_TRANS_REF_ACCOUNT = 'bank_import_trans_ref_account';

    /**
     * Default GL account for transaction reference logging
     */
    private const DEFAULT_TRANS_REF_ACCOUNT = '0000';

    /**
     * Check if transaction reference logging is enabled
     *
     * @return bool True if enabled, false if disabled
     */
    public static function getTransRefLoggingEnabled(): bool
    {
        $value = get_company_pref(self::KEY_TRANS_REF_LOGGING);
        
        // Default to true (enabled) if not set
        if ($value === null || $value === '') {
            return true;
        }
        
        return (bool)(int)$value;
    }

    /**
     * Get GL account code for transaction reference logging
     *
     * @return string GL account code (e.g., '0000')
     */
    public static function getTransRefAccount(): string
    {
        $account = get_company_pref(self::KEY_TRANS_REF_ACCOUNT);
        
        // Default to '0000' if not set
        return $account ?? self::DEFAULT_TRANS_REF_ACCOUNT;
    }

    /**
     * Set transaction reference logging enabled/disabled
     *
     * @param bool $enabled True to enable, false to disable
     * @return void
     */
    public static function setTransRefLoggingEnabled(bool $enabled): void
    {
        set_company_pref(self::KEY_TRANS_REF_LOGGING, $enabled ? '1' : '0');
    }

    /**
     * Set GL account code for transaction reference logging
     *
     * Validates that the account exists before saving.
     *
     * @param string $accountCode GL account code
     * @return void
     * @throws \InvalidArgumentException if account doesn't exist
     */
    public static function setTransRefAccount(string $accountCode): void
    {
        // Validate account exists
        if (!self::glAccountExists($accountCode)) {
            throw new \InvalidArgumentException(
                "GL account '{$accountCode}' does not exist"
            );
        }
        
        set_company_pref(self::KEY_TRANS_REF_ACCOUNT, $accountCode);
    }

    /**
     * Check if a GL account exists in the chart of accounts
     *
     * @param string $accountCode GL account code to check
     * @return bool True if account exists, false otherwise
     */
    private static function glAccountExists(string $accountCode): bool
    {
        // In production, this queries FrontAccounting's database
        // For testing, we can mock get_company_pref() to bypass DB
        
        if (!function_exists('db_escape')) {
            // Not in FrontAccounting context (e.g., unit tests)
            // Allow any account code for testing
            return true;
        }
        
        $sql = "SELECT COUNT(*) as count FROM " . TB_PREF . "chart_master 
                WHERE account_code = " . db_escape($accountCode);
        
        $result = db_query($sql, "Failed to check GL account");
        $row = db_fetch($result);
        
        return (int)$row['count'] > 0;
    }

    /**
     * Get all configuration settings as array
     *
     * Useful for debugging or exporting settings.
     *
     * @return array<string, mixed> Configuration settings
     */
    public static function getAllSettings(): array
    {
        return [
            'trans_ref_logging_enabled' => self::getTransRefLoggingEnabled(),
            'trans_ref_account' => self::getTransRefAccount(),
        ];
    }

    /**
     * Reset all settings to defaults
     *
     * Useful for testing or resetting module configuration.
     *
     * @return void
     */
    public static function resetToDefaults(): void
    {
        self::setTransRefLoggingEnabled(true);
        set_company_pref(self::KEY_TRANS_REF_ACCOUNT, self::DEFAULT_TRANS_REF_ACCOUNT);
    }
}
