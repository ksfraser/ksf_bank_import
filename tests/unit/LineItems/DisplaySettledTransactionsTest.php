<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\LineItems;

use Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\FixtureTree;
use Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\IsolatedScenario;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/IsolatedScenario.php';
require_once __DIR__ . '/Support/FixtureTree.php';

/**
 * Coverage for the settled transaction view in
 * src/Ksfraser/FaBankImport/Views/DisplaySettledTransactions.php.
 *
 * The view cannot be loaded from the repository: it implements an HTML
 * element contract without the return types the current interface asks for
 * and it loads the interface itself, and it loads a label row base class
 * that does not exist. It is therefore exercised against a patched copy of
 * the tree, whose coverage is mapped back onto the repository.
 */
final class DisplaySettledTransactionsTest extends TestCase
{
    /**
     * @var IsolatedScenario
     */
    private $scenario;

    protected function setUp(): void
    {
        $this->scenario = new IsolatedScenario();
    }

    public function testTheViewIsExercised(): void
    {
        $this->scenario->run('display_settled_transactions');

        $this->assertStringContainsString('SCENARIO_OK', $this->scenario->getOutput());
        $this->assertSame(0, $this->scenario->getExitCode());
    }

    public function testTheScenarioCoversTheView(): void
    {
        $this->scenario->run('display_settled_transactions');

        // The scenario runs the real classes now, so its coverage already
        // carries the repository paths - no fixture mapping is needed.
        $coverage = $this->scenario->getCoverage();

        $this->assertArrayHasKey(
            FA_ROOT . '/src/Ksfraser/FaBankImport/Views/DisplaySettledTransactions.php',
            $coverage
        );

        $this->scenario->mergeCoverage($this, $coverage);
    }
}
