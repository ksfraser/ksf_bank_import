<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\LineItems;

use Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\IsolatedScenario;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/IsolatedScenario.php';

/**
 * Coverage for the legacy line item model in src/Ksfraser/FaBankImport/Model/BiLineItemModel.php.
 *
 * The class declares Ksfraser\FaBankImport\Model\BiLineItemModel, the same
 * name the model in src/Ksfraser/FaBankImport/model/ uses, so it can only be
 * loaded in a process of its own.
 */
final class LegacyModelTest extends TestCase
{
    /**
     * @var IsolatedScenario
     */
    private $scenario;

    protected function setUp(): void
    {
        $this->scenario = new IsolatedScenario();
    }

    public function testTheLegacyModelCanBeExercised(): void
    {
        $this->scenario->run('legacy_model');

        $this->assertStringContainsString('SCENARIO_OK', $this->scenario->getOutput());
        $this->assertSame(0, $this->scenario->getExitCode());
    }

    public function testTheScenarioCoversTheLegacyModel(): void
    {
        $this->scenario->run('legacy_model');

        $this->assertNotEmpty($this->scenario->getCoverage());
        $this->scenario->mergeCoverage($this);
    }
}
