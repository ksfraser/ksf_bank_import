<?php

declare(strict_types=1);

namespace Ksfraser\FA\Auth;

/**
 * SRP: Resolve FrontAccounting user session information.
 */
final class UserSession
{
    /**
     * Get the current username from the FrontAccounting session.
     * 
     * @return string The username or 'unknown' if not set.
     */
    public static function getCurrentUsername(): string
    {
        // FrontAccounting stores username in $_SESSION['wa_current_user']->username
        if (isset($_SESSION['wa_current_user']) && is_object($_SESSION['wa_current_user'])) {
            return (string)($_SESSION['wa_current_user']->username ?? 'unknown');
        }
        
        return 'unknown';
    }
}
