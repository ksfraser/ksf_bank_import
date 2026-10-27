<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\GapClosure;

/**
 * Coverage for the code that can only run in a process of its own.
 *
 * The scenarios behind these tests exercise the classes that the PHPUnit
 * process cannot reach:
 *
 *  - the facade for the FrontAccounting UI functions only delegates when
 *    the installation is below FA_ROOT;
 *  - the result display falls back to the plain notification when the
 *    installation has no warning helper;
 *  - the path resolver reports that the installation has no helper for the
 *    company directory.
 */
final class IsolatedEnvironmentTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Ksfraser\FaBankImport\Tests\Unit\GapClosure\Support\IsolatedScenario
     */
    private $scenario;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        require_once __DIR__ . '/Support/IsolatedScenario.php';

        $this->scenario = new \Ksfraser\FaBankImport\Tests\Unit\GapClosure\Support\IsolatedScenario();
    }

    /**
     * @return void
     */
    public function testTheUiFacadeDelegatesToTheInstallation(): void
    {
        $this->scenario->run('fa_ui_functions');

        $this->assertStringContainsString('SCENARIO_OK', $this->scenario->getOutput());
        $this->assertStringContainsString("<td class='label'>A label</td>", $this->scenario->getOutput());
        $this->assertStringContainsString('<table class=\'tablestyle2\'', $this->scenario->getOutput());
        $this->assertSame(0, $this->scenario->getExitCode());
    }

    /**
     * @return void
     */
    public function testTheWarningFallsBackToTheNotification(): void
    {
        $this->scenario->run('transaction_result_display');

        $this->assertStringContainsString('SCENARIO_OK', $this->scenario->getOutput());
        $this->assertStringContainsString('NOTIFICATION: Careful', $this->scenario->getOutput());
        $this->assertSame(0, $this->scenario->getExitCode());
    }

    /**
     * @return void
     */
    public function testThePathResolverReportsAMissingHelper(): void
    {
        $this->scenario->run('path_resolver_without_company_path');

        $this->assertStringContainsString('SCENARIO_OK', $this->scenario->getOutput());
        $this->assertStringContainsString('company_path() is not available', $this->scenario->getOutput());
        $this->assertSame(0, $this->scenario->getExitCode());
    }

    /**
     * @return void
     */
    public function testTheScenariosAreCovered(): void
    {
        $scenarios = [
            'fa_ui_functions',
            'transaction_result_display',
            'path_resolver_without_company_path',
            'fa_ui_functions_without_root',
            'deprecated_aliases',
            'registry_unreadable_directory',
            'discovery_unreadable_paths',
        ];

        foreach ($scenarios as $name) {
            $this->scenario->run($name);

            $this->assertNotEmpty($this->scenario->getCoverage(), $name . ' collected no coverage.');
            $this->scenario->mergeCoverage($this);
        }
    }

    /**
     * @return void
     */
    public function testTheFacadeNamesTheInstallationItself(): void
    {
        $this->scenario->run('fa_ui_functions_without_root');

        $this->assertStringContainsString('SCENARIO_OK', $this->scenario->getOutput());
        $this->assertStringContainsString('UI_AVAILABLE: false', $this->scenario->getOutput());
        $this->assertSame(0, $this->scenario->getExitCode());
    }

    /**
     * @return void
     */
    public function testTheDeprecatedNamesAreAliased(): void
    {
        $this->scenario->run('deprecated_aliases');

        $output = $this->scenario->getOutput();

        $this->assertStringContainsString('SCENARIO_OK', $output);
        $this->assertStringContainsString('ALIAS Ksfraser\HTML\Elements\HtmlHidden: true', $output);
        $this->assertSame(0, $this->scenario->getExitCode());
    }

    /**
     * When the scanner is denied its directory, the registry keeps no type.
     *
     * The scenario denies the partner type directory with open_basedir, so
     * glob() fails and loadTypes() returns without registering anything -
     * and asking for a type twice still finds none.
     *
     * @return void
     */
    public function testTheRegistryScannerKeepsNoTypeWhenDenied(): void
    {
        $this->scenario->run('registry_unreadable_directory');

        $output = $this->scenario->getOutput();

        $this->assertStringContainsString('SCENARIO_OK', $output);
        $this->assertStringContainsString('SCAN_DENIED: true', $output);
        $this->assertStringContainsString('SCAN_STILL_DENIED: true', $output);
        $this->assertSame(0, $this->scenario->getExitCode());
    }

    /**
     * When the handlers directory is out of reach, the processor registers
     * no handler and leaves the request as it came.
     *
     * A first construction runs while the stat cache still knows the
     * directory (glob fails), a second one after the cache is cleared (the
     * directory lookup itself fails).
     *
     * @return void
     */
    public function testHandlerDiscoveryRegistersNothingWhenDenied(): void
    {
        $this->scenario->run('discovery_unreadable_paths');

        $output = $this->scenario->getOutput();

        $this->assertStringContainsString('SCENARIO_OK', $output);
        $this->assertStringContainsString('SCAN_DENIED: true', $output);
        $this->assertStringContainsString('BAIL_OUT: true', $output);
        $this->assertSame(0, $this->scenario->getExitCode());
    }
}
