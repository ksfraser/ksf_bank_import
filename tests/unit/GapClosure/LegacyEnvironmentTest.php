<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\GapClosure;

use Ksfraser\FaBankImport\Tests\Unit\GapClosure\Support\IsolatedScenario;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/IsolatedScenario.php';
require_once __DIR__ . '/../LineItems/Support/FixtureTree.php';

use Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\FixtureTree;

/**
 * Coverage for the code that needs the legacy environment.
 *
 * The scenarios behind these tests exercise the classes that the PHPUnit
 * process cannot reach:
 *
 *  - the legacy line item model falls back to the raw account name when
 *    the helper of the installation fails;
 *  - the bank account provider loads the legacy helper of the module
 *    common package;
 *  - the matching finder loads the legacy helper as well;
 *  - the settled transaction tables build their rows themselves.
 */
final class LegacyEnvironmentTest extends TestCase
{
    /**
     * @var IsolatedScenario
     */
    private $scenario;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->scenario = new IsolatedScenario();
    }

    /**
     * @return void
     */
    public function testTheModelFallsBackToTheRawAccountName(): void
    {
        $this->scenario->run('bi_line_item_model');

        $this->assertStringContainsString('SCENARIO_OK', $this->scenario->getOutput());
        $this->assertSame(0, $this->scenario->getExitCode());
    }

    /**
     * The read-only accessor serves the protected values, and answers null
     * for names the model does not have.
     *
     * @return void
     */
    public function testTheReadonlyAccessorAnswersKnownAndUnknownNames(): void
    {
        $this->scenario->run('bi_line_item_model');

        $output = $this->scenario->getOutput();

        $this->assertStringContainsString('GET_LABEL: Credit', $output);
        $this->assertStringContainsString('GET_MISSING: true', $output);
    }

    /**
     * @return void
     */
    public function testTheProviderLoadsTheLegacyHelper(): void
    {
        $this->scenario->run('fa_bank_accounts_provider');

        $this->assertStringContainsString('SCENARIO_OK', $this->scenario->getOutput());
        $this->assertSame(0, $this->scenario->getExitCode());
    }

    /**
     * @return void
     */
    public function testTheFinderLoadsTheLegacyHelper(): void
    {
        $this->scenario->run('matching_jes_finder');

        $this->assertStringContainsString('SCENARIO_OK', $this->scenario->getOutput());
        $this->assertStringContainsString('LEGACY_AVAILABLE: true', $this->scenario->getOutput());
        $this->assertSame(0, $this->scenario->getExitCode());
    }

    /**
     * @return void
     */
    public function testTheSettledTableBuildsItsRows(): void
    {
        $this->scenario->run('settled_table');

        $this->assertStringContainsString('SCENARIO_OK', $this->scenario->getOutput());
        $this->assertSame(0, $this->scenario->getExitCode());
    }

    /**
     * @return void
     */
    public function testTheScenariosAreCovered(): void
    {
        foreach (['bi_line_item_model', 'fa_bank_accounts_provider', 'matching_jes_finder'] as $name) {
            $this->scenario->run($name);

            $this->assertNotEmpty($this->scenario->getCoverage(), $name . ' collected no coverage.');
            $this->scenario->mergeCoverage($this);
        }
    }

    /**
     * @return void
     */
    public function testTheSettledTableScenarioIsCovered(): void
    {
        $this->scenario->run('settled_table');

        $output = $this->scenario->getOutput();
        $matched = preg_match('/FIXTURE_SRC: (\S+)/', $output, $matches);
        $this->assertSame(1, $matched, 'The scenario did not report its fixture tree.');

        $mapped = FixtureTree::mapCoverage(
            $this->scenario->getCoverage(),
            $matches[1],
            FA_ROOT
        );

        $this->assertArrayHasKey(
            FA_ROOT . '/src/Ksfraser/FaBankImport/Views/DisplaySettledTransactions.php',
            $mapped
        );

        $this->scenario->mergeCoverage($this, $mapped);
    }
}
