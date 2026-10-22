<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\LineItems;

use Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\IsolatedScenario;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/IsolatedScenario.php';

/**
 * Coverage for the legacy bank account and journal entry lookups.
 *
 * Both classes load their collaborators from a path that is relative to the
 * file, so they are exercised in a process of their own where the legacy
 * helper can be published next to the repository.
 */
final class LegacyLookupsTest extends TestCase
{
    /**
     * @var IsolatedScenario
     */
    private $scenario;

    protected function setUp(): void
    {
        $this->scenario = new IsolatedScenario();
    }

    public function testTheBankAccountLookupIsExercised(): void
    {
        $this->scenario->run('bank_account_by_number');

        $this->assertStringContainsString('SCENARIO_OK', $this->scenario->getOutput());
        $this->assertSame(0, $this->scenario->getExitCode());
    }

    public function testTheJournalEntryLookupIsExercised(): void
    {
        $this->scenario->run('matching_jes');

        $this->assertStringContainsString('SCENARIO_OK', $this->scenario->getOutput());
        $this->assertSame(0, $this->scenario->getExitCode());
    }

    public function testTheJournalEntryLookupHandlesAMissingHelper(): void
    {
        $this->scenario->run('matching_jes', ['missing']);

        $this->assertStringContainsString('SCENARIO_OK', $this->scenario->getOutput());
        $this->assertSame(0, $this->scenario->getExitCode());
    }

    public function testTheJournalEntryLookupHandlesAFailingSearch(): void
    {
        $this->scenario->run('matching_jes', ['throwing']);

        $this->assertStringContainsString('SCENARIO_OK', $this->scenario->getOutput());
        $this->assertSame(0, $this->scenario->getExitCode());
    }

    public function testTheScenariosCoverTheLookups(): void
    {
        $this->scenario->run('bank_account_by_number');
        $this->assertNotEmpty($this->scenario->getCoverage());
        $this->scenario->mergeCoverage($this);

        $this->scenario->run('matching_jes');
        $this->assertNotEmpty($this->scenario->getCoverage());
        $this->scenario->mergeCoverage($this);

        $this->scenario->run('matching_jes', ['missing']);
        $this->assertNotEmpty($this->scenario->getCoverage());
        $this->scenario->mergeCoverage($this);

        $this->scenario->run('matching_jes', ['throwing']);
        $this->assertNotEmpty($this->scenario->getCoverage());
        $this->scenario->mergeCoverage($this);
    }
}
