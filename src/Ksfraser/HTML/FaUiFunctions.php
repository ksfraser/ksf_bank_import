<?php

namespace Ksfraser\HTML;

if (!defined('FA_ROOT')) {
    define('FA_ROOT', realpath(__DIR__ . '/../../..'));
}

// Load FA UI functions in global namespace when available.
$uiFiles = [
    FA_ROOT . '/includes/ui/ui_input.inc',
    FA_ROOT . '/includes/ui/ui_lists.inc',
    FA_ROOT . '/includes/ui/ui_controls.inc',
];

foreach ($uiFiles as $uiFile) {
    if (is_string($uiFile) && file_exists($uiFile)) {
        require_once $uiFile;
    }
}

/**
 * Facade for Front Accounting UI functions
 * This allows us to decouple our HTML components from FA's UI functions
 */
class FaUiFunctions {
    const TABLESTYLE2 = 2; // Matching FA's constant

    private static function shouldUseGlobalUiFunctions(): bool
    {
        return (getenv('APP_ENV') !== 'testing' && ($_ENV['APP_ENV'] ?? null) !== 'testing');
    }

    public static function label_row($label, $content, $params="")
    {
        // Check for function in global namespace
        if (self::shouldUseGlobalUiFunctions() && function_exists('\\label_row')) {
            call_user_func('\\label_row', $label, $content, $params);
        } else {
            $attributeText = $params !== '' ? ' ' . trim((string)$params) : '';
            echo "<tr><td class='label'>$label</td><td$attributeText>$content</td></tr>";
        }
    }

    public static function start_table($type = self::TABLESTYLE2, $params="")
    {
        if (self::shouldUseGlobalUiFunctions() && function_exists('\\start_table')) {
            call_user_func('\\start_table', $type, $params);
        } else {
            echo "<table class='tablestyle$type' $params>\n";
        }
    }

    public static function end_table($breaks=0)
    {
        if (self::shouldUseGlobalUiFunctions() && function_exists('\\end_table')) {
            call_user_func('\\end_table', $breaks);
        } else {
            echo "</table>\n";
        }
    }
}