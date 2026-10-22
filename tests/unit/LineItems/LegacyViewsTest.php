<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\LineItems;

use Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\IsolatedScenario;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/IsolatedScenario.php';

/**
 * Coverage for the legacy views under src/Ksfraser/FaBankImport/Views/.
 *
 * The views cannot be loaded in the PHPUnit process: several of them declare
 * classes the test doubles of other test files also declare, and they all
 * implement an HTML element contract the current interface no longer
 * matches. They are therefore exercised in a process of their own.
 */
final class LegacyViewsTest extends TestCase
{
    /**
     * @var IsolatedScenario
     */
    private $scenario;

    protected function setUp(): void
    {
        $this->scenario = new IsolatedScenario();
    }

    public function testTheViewsAreExercised(): void
    {
        $this->scenario->run('fa_bank_import_views');

        $this->assertStringContainsString('SCENARIO_OK', $this->scenario->getOutput());
        $this->assertSame(0, $this->scenario->getExitCode());
    }

    public function testTheScenarioCoversTheViews(): void
    {
        $this->scenario->run('fa_bank_import_views');

        $this->assertNotEmpty($this->scenario->getCoverage());
        $this->scenario->mergeCoverage($this);
    }
}
