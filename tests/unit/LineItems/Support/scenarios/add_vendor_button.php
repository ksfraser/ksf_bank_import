<?php

declare(strict_types=1);

/**
 * Scenario: the add vendor button in
 * src/Ksfraser/FaBankImport/views/AddVendorButton.php.
 *
 * The button loads a decorator from a path that does not exist, so the file
 * cannot be loaded from the repository. The scenario therefore runs against a
 * patched copy of the tree, which the coverage is mapped back onto.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\scenarios
 * @license    MIT
 */

require_once __DIR__ . '/../fa_stubs.php';
require_once __DIR__ . '/../view_stubs.php';
require_once __DIR__ . '/../FixtureTree.php';

use Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\FixtureTree;

$tree = new FixtureTree(KSF_LINEITEMS_ROOT);
$srcDir = $tree->create();

$tree->copyFile('Ksfraser/FaBankImport/views/AddVendorButton.php');

// The decorator the button loads.
$tree->writeFile(
    'Ksfraser/FaBankImport/views/HTML/HTML_ROW_LABELDecorator.php',
    "<?php\n\n// The decorator the add vendor button loads.\n"
);

register_shutdown_function(function () use ($tree) {
    $tree->cleanup();
});

echo 'FIXTURE_SRC: ' . $srcDir . "\n";

class_alias(
    \Ksfraser\HTML\Composites\HTML_ROW_LABELDecorator::class,
    'Ksfraser\FaBankImport\HTML_ROW_LABELDecorator'
);

require_once $srcDir . '/Ksfraser/FaBankImport/views/AddVendorButton.php';

$button = new \Ksfraser\FaBankImport\AddVendorButton(11);
$button->toHTML();

echo "SCENARIO_OK\n";
