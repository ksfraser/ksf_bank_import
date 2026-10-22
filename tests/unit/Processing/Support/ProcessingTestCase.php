<?php

declare(strict_types=1);

/**
 * Shared base class for the transaction-processing tests.
 *
 * Loads the FrontAccounting doubles used by the handlers and resets the
 * stub registry between tests.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Support
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\Support;

use Ksfraser\FaBankImport\Services\ReferenceNumberService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/FaStubRegistry.php';
require_once __DIR__ . '/FakeReferenceGenerator.php';
require_once __DIR__ . '/handler_fa_stubs.php';
require_once __DIR__ . '/fa_global_stubs.php';
require_once __DIR__ . '/fa_bank_import_class_stubs.php';
require_once __DIR__ . '/global_sum_charges.php';

/**
 * Base class wiring the FrontAccounting doubles into every test.
 */
abstract class ProcessingTestCase extends TestCase
{
    /**
     * Reference generator that was active before the test.
     *
     * @var mixed
     */
    private $previousRefs;

    /**
     * Reset the stub registry before every test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        FaStubRegistry::reset();

        // Handlers that are built by the auto-discovery use FrontAccounting's
        // global $Refs object to build their reference numbers.
        $this->previousRefs = $GLOBALS['Refs'] ?? null;
        $GLOBALS['Refs'] = new FakeReferenceGenerator();
    }

    /**
     * Reset the stub registry after every test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        FaStubRegistry::reset();

        if ($this->previousRefs === null) {
            unset($GLOBALS['Refs']);
        } else {
            $GLOBALS['Refs'] = $this->previousRefs;
        }

        parent::tearDown();
    }

    /**
     * Make the global sumCharges() helper available.
     *
     * The handlers probe the global scope for the helper before they use it,
     * so the tests that assert on a bank charge need it to be present.
     *
     * @return void
     */
    protected function ensureGlobalSumCharges(): void
    {
        ksf_processing_define_global_sum_charges();
    }

    /**
     * Build a reference service backed by a deterministic generator.
     *
     * @param string $prefix Reference prefix.
     *
     * @return ReferenceNumberService
     */
    protected function referenceService(string $prefix = 'REF'): ReferenceNumberService
    {
        return new ReferenceNumberService(new FakeReferenceGenerator($prefix));
    }
}
