<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\ReferenceNumberService;
use Ksfraser\FaBankImport\Tests\Unit\Services\Support\FaDbFake;

require_once __DIR__ . '/Support/fa_db_stubs_services.php';

/**
 * Behavioural tests for the reference number service.
 */
final class ReferenceNumberServiceCoverageTest extends TestCase
{
    /** @var FaDbFake|null */
    private $fake;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fake = FaDbFake::activate();
    }

    protected function tearDown(): void
    {
        FaDbFake::deactivate();
        $this->fake = null;

        parent::tearDown();
    }

    public function testGetUniqueReferenceReturnsTheReferenceFromTheInjectedGenerator(): void
    {
        $generator = new class {
            /** @var array<int,int> */
            public $types = [];

            public function get_next($transType)
            {
                $this->types[] = $transType;

                return 'REF-0001';
            }
        };

        $service = new ReferenceNumberService($generator);

        $this->assertSame('REF-0001', $service->getUniqueReference(12));
        $this->assertSame([12], $generator->types);
    }

    public function testGetUniqueReferenceKeepsGeneratingUntilTheReferenceIsUnused(): void
    {
        $generator = new class {
            /** @var int */
            private $calls = 0;

            public function get_next($transType)
            {
                $this->calls++;

                return $this->calls === 1 ? 'TAKEN' : 'FREE';
            }
        };

        // First reference is "taken", the second one is free.
        $this->fake->referenceResults = [false, true];

        $service = new ReferenceNumberService($generator);

        $this->assertSame('FREE', $service->getUniqueReference(12));
    }

    public function testGetUniqueReferenceFallsBackToTheGlobalRefsObject(): void
    {
        $globalRefs = new class {
            public function get_next($transType)
            {
                return 'GLOBAL-1';
            }
        };

        $previous = isset($GLOBALS['Refs']) ? $GLOBALS['Refs'] : null;
        $GLOBALS['Refs'] = $globalRefs;

        try {
            $service = new ReferenceNumberService();

            $this->assertSame('GLOBAL-1', $service->getUniqueReference(27));
        } finally {
            if ($previous === null) {
                unset($GLOBALS['Refs']);
            } else {
                $GLOBALS['Refs'] = $previous;
            }
        }
    }
}
