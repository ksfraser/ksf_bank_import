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

    public function testTheScenarioCoversTheWrapper(): void
    {
        $this->scenario->run('fa_gl_wrapper');

        $this->assertNotEmpty($this->scenario->getCoverage());
        $this->scenario->mergeCoverage($this);
    }
}
