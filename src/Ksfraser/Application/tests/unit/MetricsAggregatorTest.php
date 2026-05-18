<?php

/**
 * DEPRECATED - MetricsAggregatorTest (Application variant)
 * 
 * This test has been deprecated due to external vfsStream dependency.
 * See tests/unit/DEPRECATED_MetricsAggregatorTest.info for details.
 * 
 * Reason: Requires org\bovigo\vfs\vfsStream (not in composer.json)
 * Status: Optional feature - not part of approved test suite
 */

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * @deprecated This class is no longer maintained
 */
class MetricsAggregatorTest extends TestCase
{
    /**
     * Placeholder test to prevent fatal errors
     * 
     * @test
     */
    public function testDeprecated()
    {
        $this->markTestSkipped('MetricsAggregatorTest (Application) has been deprecated - requires org.bovigo/vfsstream');
    }
}
