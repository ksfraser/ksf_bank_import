<?php

namespace Ksfraser\HTML;

if (!defined('FA_ROOT')) {
    define('FA_ROOT', realpath(__DIR__ . '/../../../..'));
}

// Load FA UI functions in global namespace (only available inside a
// FrontAccounting install). Track availability explicitly: test environments
// may define function_exists-guarded inert stubs for these names, which would
// otherwise make the delegation below swallow output.
if (!defined('KSF_FA_UI_AVAILABLE')) {
    define('KSF_FA_UI_AVAILABLE', file_exists(FA_ROOT . "/includes/ui/ui_input.inc"));
}

if (KSF_FA_UI_AVAILABLE) {
    require_once(FA_ROOT . "/includes/ui/ui_input.inc");
    require_once(FA_ROOT . "/includes/ui/ui_lists.inc");
    require_once(FA_ROOT . "/includes/ui/ui_controls.inc");
}

/**
 * Facade for Front Accounting UI functions
 * This allows us to decouple our HTML components from FA's UI functions
 */
class FaUiFunctions {
    const TABLESTYLE2 = 2; // Matching FA's constant

    public static function label_row($label, $content, $params="")
    {
        if (KSF_FA_UI_AVAILABLE) {
            call_user_func('\\label_row', $label, $content, $params);
        } else {
            echo "<tr><td class='label'>$label</td><td $params>$content</td></tr>";
        }
    }

    public static function start_table($type = self::TABLESTYLE2, $params="")
    {
        if (KSF_FA_UI_AVAILABLE) {
            call_user_func('\\start_table', $type, $params);
        } else {
            echo "<table class='tablestyle$type' $params>\n";
        }
    }

    public static function end_table($breaks=0)
    {
        if (KSF_FA_UI_AVAILABLE) {
            call_user_func('\\end_table', $breaks);
        } else {
            echo "</table>\n";
        }
    }
}