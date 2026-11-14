<?php

/**
 * A mail() mock for the Ksfraser\FaBankImport\Services namespace.
 *
 * PHP does not allow redeclaring the built-in global mail(), and
 * function_exists('mail') is always true. Email paths in this app are
 * executed from namespaced classes, and an unqualified mail() call resolves
 * against the call site's namespace first and only falls back to the global
 * scope afterwards. Declaring Ksfraser\FaBankImport\Services\mail() here
 * therefore intercepts those calls without touching the global function.
 */

namespace Ksfraser\FaBankImport\Services;

$GLOBALS['mock_mail_log'] = [];

if (!function_exists(__NAMESPACE__ . '\\mail')) {
    function mail($to, $subject, $message, $headers)
    {
        $GLOBALS['mock_mail_log'][] = [
            'to' => $to,
            'subject' => $subject,
            'message' => $message,
            'headers' => $headers
        ];
        return true;
    }
}
