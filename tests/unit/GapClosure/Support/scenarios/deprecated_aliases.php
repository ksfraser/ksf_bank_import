<?php

declare(strict_types=1);

/**
 * Scenario: the deprecated class aliases.
 *
 * The alias files are loaded while the test bootstrap loads the legacy
 * views, which happens before the coverage of the PHPUnit process starts.
 * The scenario loads them again in a process whose coverage is already
 * running.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\GapClosure\Support\scenarios
 * @license    MIT
 */

$aliases = [
    'Ksfraser\HTML\Elements\HtmlButton',
    'Ksfraser\HTML\Elements\HtmlHidden',
    'Ksfraser\HTML\Elements\HtmlInput',
    'Ksfraser\HTML\Elements\HtmlOption',
    'Ksfraser\HTML\Elements\HtmlSelect',
];

foreach ($aliases as $alias) {
    echo 'ALIAS ' . $alias . ': ' . var_export(class_exists($alias), true) . "\n";
}

echo "SCENARIO_OK\n";
