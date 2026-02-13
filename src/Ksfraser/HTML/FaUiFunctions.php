<?php

namespace Ksfraser\HTML;

if (!defined('FA_ROOT')) {
    define('FA_ROOT', realpath(__DIR__ . '/../../../..'));
}

// Load FA UI functions in global namespace
$uiInput = FA_ROOT . "/includes/ui/ui_input.inc";
$uiLists = FA_ROOT . "/includes/ui/ui_lists.inc";
$uiControls = FA_ROOT . "/includes/ui/ui_controls.inc";

if (is_file($uiInput)) {
    require_once($uiInput);
}
if (is_file($uiLists)) {
    require_once($uiLists);
}
if (is_file($uiControls)) {
    require_once($uiControls);
}

/**
 * Facade for Front Accounting UI functions
 * This allows us to decouple our HTML components from FA's UI functions
 */
class FaUiFunctions {
    const TABLESTYLE2 = 2; // Matching FA's constant

    public static function label_row($label, $content, $params="")
    {
        echo "<tr><td class='label'>$label</td><td $params>$content</td></tr>";
    }

    public static function start_table($type = self::TABLESTYLE2, $params="")
    {
        if (function_exists('\\start_table')) {
            call_user_func('\\start_table', $type, $params);
        } else {
            echo "<table class='tablestyle$type' $params>\n";
        }
    }

    public static function end_table($breaks=0)
    {
        if (function_exists('\\end_table')) {
            call_user_func('\\end_table', $breaks);
        } else {
            echo "</table>\n";
        }
    }
}