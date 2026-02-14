<?php

declare(strict_types=1);

namespace Ksfraser\HTML\Ajax;

/**
 * SRP utility for activating FrontAccounting Ajax targets by DIV id.
 */
final class DivActivator
{
    /**
     * Activate a FrontAccounting Ajax target safely.
     *
     * @param string $divId
     * @return bool True when activation was attempted successfully.
     */
    public static function activate(string $divId): bool
    {
        if ($divId === '') {
            return false;
        }

        if (!isset($GLOBALS['Ajax']) || !is_object($GLOBALS['Ajax']) || !method_exists($GLOBALS['Ajax'], 'activate')) {
            error_log('[bank_import] Ajax object not available for div activation: ' . $divId);
            return false;
        }

        $GLOBALS['Ajax']->activate($divId);
        return true;
    }

    /**
     * Convenience method for the common bank import target.
     *
     * @return bool
     */
    public static function activateDocTable(): bool
    {
        return self::activate('doc_tbl');
    }
}
