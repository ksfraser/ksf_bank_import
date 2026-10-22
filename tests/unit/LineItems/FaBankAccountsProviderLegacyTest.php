<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\LineItems;

use Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\IsolatedScenario;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/IsolatedScenario.php';

/**
 * Coverage for the FA backed bank account details provider.
 *
 * The provider refuses to run while the legacy package is unavailable, so
 * the part that talks to the legacy class is exercised in a process of its
 * own.
 */
final class FaBankAccountsProviderLegacyTest extends TestCase
{
    /**
     * @var IsolatedScenario
     */
    private $scenario;

    protected function setUp(): void
    {
        $this->scenario = new IsolatedScenario();
    }

    public function testTheProviderLooksTheAccountUp(): void
    {
        $this->scenario->run('fa_bank_accounts_provider', ['double']);

        $this->assertStringContainsString('SCENARIO_OK', $this->scenario->getOutput());
        $this->assertStringContainsString('DETAILS:', $this->scenario->getOutput());
        $this->assertSame(0, $this->scenario->getExitCode());
    }

    public function testTheScenarioCoversTheProvider(): void
    {
        $this->scenario->run('fa_bank_accounts_provider', ['double']);

        $this->assertNotEmpty($this->scenario->getCoverage());
        $this->scenario->mergeCoverage($this);
    }
}
