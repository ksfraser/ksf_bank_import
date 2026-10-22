<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\LineItems;

use Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\FixtureTree;
use Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\IsolatedScenario;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/IsolatedScenario.php';
require_once __DIR__ . '/Support/FixtureTree.php';

/**
 * Coverage for the add vendor button in
 * src/Ksfraser/FaBankImport/views/AddVendorButton.php.
 *
 * The button loads a decorator from a path that does not exist, so the file
 * cannot be loaded from the repository. It is therefore exercised against a
 * patched copy of the tree, whose coverage is mapped back onto the
 * repository.
 */
final class AddVendorButtonTest extends TestCase
{
    /**
     * @var IsolatedScenario
     */
    private $scenario;

    protected function setUp(): void
    {
        $this->scenario = new IsolatedScenario();
    }

    public function testTheButtonIsRendered(): void
    {
        $this->scenario->run('add_vendor_button');

        $this->assertStringContainsString('SCENARIO_OK', $this->scenario->getOutput());
        $this->assertSame(0, $this->scenario->getExitCode());
    }

    public function testTheScenarioCoversTheButton(): void
    {
        $this->scenario->run('add_vendor_button');

        $output = $this->scenario->getOutput();
        $matched = preg_match('/FIXTURE_SRC: (\S+)/', $output, $matches);
        $this->assertSame(1, $matched, 'The scenario did not report its fixture tree.');

        $mapped = FixtureTree::mapCoverage(
            $this->scenario->getCoverage(),
            $matches[1],
            FA_ROOT
        );

        $this->assertArrayHasKey(
            FA_ROOT . '/src/Ksfraser/FaBankImport/views/AddVendorButton.php',
            $mapped
        );

        $this->scenario->mergeCoverage($this, $mapped);
    }
}
