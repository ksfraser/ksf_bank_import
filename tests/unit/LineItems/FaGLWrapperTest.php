<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\LineItems;

use Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\IsolatedScenario;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/IsolatedScenario.php';

/**
 * Coverage for the GL wrapper in src/Ksfraser/FaBankImport/FaGLWrapper.php.
 *
 * The wrapper loads the legacy fa_gl helper from a path that is missing its
 * separator, so the include never succeeds and the constructor always
 * throws. The scenario is run in a process of its own because the test
 * doubles of the other test files declare a class with the same name.
 */
final class FaGLWrapperTest extends TestCase
{
    /**
     * @var IsolatedScenario
     */
    private $scenario;

    protected function setUp(): void
    {
        $this->scenario = new IsolatedScenario();
    }

    public function testTheWrapperRefusesToBuildWithoutTheLegacyHelper(): void
    {
        $this->scenario->run('fa_gl_wrapper');

        $this->assertStringContainsString('SCENARIO_OK', $this->scenario->getOutput());
        $this->assertStringContainsString('CAUGHT:', $this->scenario->getOutput());
        $this->assertSame(0, $this->scenario->getExitCode());
    }

    /**
     * When the helper file is out of reach, the wrapper makes the
     * construction fail loudly.
     *
     * @return void
     */
    public function testTheWrapperFailsLoudlyWhenTheHelperFileIsDenied(): void
    {
        $this->scenario->run('fa_gl_wrapper_include_denied');

        $output = $this->scenario->getOutput();

        $this->assertStringContainsString('SCENARIO_OK', $output);
        $this->assertStringContainsString('CAUGHT:', $output);
        $this->assertStringNotContainsString('NO_EXCEPTION', $output);
        $this->assertSame(0, $this->scenario->getExitCode());
        $this->scenario->mergeCoverage($this);
    }

    /**
     * The success path builds the wrapper on top of the helper.
     *
     * The scenario defines the helper class itself and - where the target
     * file is missing - publishes the guarded double next to the module for
     * the run, so the wrapper's include call returns true and the matching
     * retrieval goes through the legacy gateway. Nothing outside the run
     * survives, and a helper file that was already there is left untouched.
     *
     * @return void
     */
    public function testTheWrapperBuildsAndRetrievesTheMatchingTransactions(): void
    {
        $this->scenario->run('fa_gl_wrapper');

        $output = $this->scenario->getOutput();

        $this->assertStringContainsString('SCENARIO_OK', $output);
        $this->assertStringContainsString('CAUGHT:', $output);
        $this->assertStringContainsString('FA_GL_SOURCE:', $output);
        $this->assertStringContainsString('MATCHED: 1', $output);
        $this->assertStringContainsString('MATCHED_MEMO: A memo', $output);
        $this->assertStringNotContainsString('CAUGHT_ON_SUCCESS_PATH', $output);
        $this->assertMatchesRegularExpression('/HELPER_FILE_LEFT_BEHIND: (true|false)/', $output);
        $this->assertSame(0, $this->scenario->getExitCode());
    }

    public function testTheScenarioCoversTheWrapper(): void
    {
        foreach (['fa_gl_wrapper', 'fa_gl_wrapper_include_denied'] as $name) {
            $this->scenario->run($name);

            $this->assertNotEmpty($this->scenario->getCoverage(), $name . ' collected no coverage.');
            $this->scenario->mergeCoverage($this);
        }
    }
}
