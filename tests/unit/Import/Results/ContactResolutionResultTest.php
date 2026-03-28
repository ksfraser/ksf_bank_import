<?php

namespace Tests\Unit\Import\Results;

use Ksfraser\FaBankImport\Import\Results\ContactResolutionResult;
use PHPUnit\Framework\TestCase;

class ContactResolutionResultTest extends TestCase
{
    /**
     * Test resolved contact result (auto-matched).
     *
     * @test
     */
    public function testResolvedContactAuto(): void
    {
        $result = ContactResolutionResult::resolved(100, 'CU', 'auto');

        $this->assertTrue($result->isSuccess());
        $this->assertTrue($result->wasResolved());
        $this->assertEquals(100, $result->getContactId());
        $this->assertEquals('CU', $result->getContactType());
        $this->assertTrue($result->wasAutoMatched());
        $this->assertEquals('auto', $result->getResolutionMethod());
    }

    /**
     * Test resolved contact result (manual).
     *
     * @test
     */
    public function testResolvedContactManual(): void
    {
        $result = ContactResolutionResult::resolved(200, 'SU', 'manual');

        $this->assertTrue($result->isSuccess());
        $this->assertTrue($result->wasResolved());
        $this->assertFalse($result->wasAutoMatched());
        $this->assertEquals('manual', $result->getResolutionMethod());
    }

    /**
     * Test resolved contact result (created).
     *
     * @test
     */
    public function testResolvedContactCreated(): void
    {
        $result = ContactResolutionResult::resolved(300, 'DE', 'created');

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('created', $result->getResolutionMethod());
    }

    /**
     * Test skipped contact result.
     *
     * @test
     */
    public function testSkippedContact(): void
    {
        $result = ContactResolutionResult::skipped('No matching contact found');

        $this->assertFalse($result->isSuccess());
        $this->assertFalse($result->wasResolved());
        $this->assertNull($result->getContactId());
        $this->assertNull($result->getContactType());
        $this->assertEquals('skipped', $result->getResolutionMethod());
        $this->assertCount(1, $result->getErrors());
    }

    /**
     * Test resolved contact with no result returns false for wasResolved.
     *
     * @test
     */
    public function testUnresolvedReturnsNull(): void
    {
        $result = ContactResolutionResult::skipped('No match');

        $this->assertNull($result->getContactId());
        $this->assertNull($result->getContactType());
    }
}
