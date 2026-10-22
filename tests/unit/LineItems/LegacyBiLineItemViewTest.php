<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\LineItems;

use Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\IsolatedScenario;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/IsolatedScenario.php';

/**
 * Coverage for the two legacy line item views.
 *
 * Both declare Ksfraser\FaBankImport\View\BiLineItemView, so each of them
 * needs a process of its own. They are also declared by the test doubles of
 * the other test files, which rules out loading them in the PHPUnit process.
 */
final class LegacyBiLineItemViewTest extends TestCase
{
    /**
     * @var IsolatedScenario
     */
    private $scenario;

    protected function setUp(): void
    {
        $this->scenario = new IsolatedScenario();
    }

    public function testTheLegacyViewIsExercised(): void
    {
        $this->scenario->run('legacy_bi_line_item_view');

        $this->assertStringContainsString('SCENARIO_OK', $this->scenario->getOutput());
        $this->assertSame(0, $this->scenario->getExitCode());
    }

    public function testTheScenariosCoverTheViews(): void
    {
        $this->scenario->run('legacy_bi_line_item_view');
        $this->assertNotEmpty($this->scenario->getCoverage());
        $this->scenario->mergeCoverage($this);

    }
}
