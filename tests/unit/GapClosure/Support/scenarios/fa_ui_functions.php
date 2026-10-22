<?php

declare(strict_types=1);

/**
 * Scenario: the facade for the FrontAccounting UI functions.
 *
 * The facade only delegates to the FrontAccounting UI helpers when they are
 * available below FA_ROOT. The PHPUnit process always has FA_ROOT pointing
 * at the module, so the delegation is never taken there. The scenario
 * publishes a fixture installation and points FA_ROOT at it.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\GapClosure\Support\scenarios
 * @license    MIT
 */

$faRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ksf_gap_fa_' . uniqid('', true);

mkdir($faRoot . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'ui', 0777, true);

file_put_contents(
    $faRoot . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . 'ui_input.inc',
    "<?php\n"
    . "if (!function_exists('label_row')) {\n"
    . "    function label_row(\$label, \$value, \$params = '')\n"
    . "    {\n"
    . "        echo \"<tr><td class='label'>\$label</td><td \$params>\$value</td></tr>\";\n"
    . "    }\n"
    . "}\n"
);

file_put_contents(
    $faRoot . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . 'ui_lists.inc',
    "<?php\n"
);

file_put_contents(
    $faRoot . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . 'ui_controls.inc',
    "<?php\n"
    . "if (!function_exists('start_table')) {\n"
    . "    function start_table(\$type = 2, \$params = '')\n"
    . "    {\n"
    . "        echo \"<table class='tablestyle\$type' \$params>\\n\";\n"
    . "    }\n"
    . "}\n"
    . "if (!function_exists('end_table')) {\n"
    . "    function end_table(\$breaks = 0)\n"
    . "    {\n"
    . "        echo \"</table>\\n\";\n"
    . "    }\n"
    . "}\n"
);

register_shutdown_function(static function () use ($faRoot): void {
    foreach (glob($faRoot . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
        unlink($file);
    }
    @rmdir($faRoot . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'ui');
    @rmdir($faRoot . DIRECTORY_SEPARATOR . 'includes');
    @rmdir($faRoot);
});

define('FA_ROOT', $faRoot);

require_once KSF_GAP_ROOT . '/src/Ksfraser/HTML/FaUiFunctions.php';

\Ksfraser\HTML\FaUiFunctions::label_row('A label', 'A value', 'width="50"');
\Ksfraser\HTML\FaUiFunctions::start_table();
\Ksfraser\HTML\FaUiFunctions::end_table();

echo "SCENARIO_OK\n";
