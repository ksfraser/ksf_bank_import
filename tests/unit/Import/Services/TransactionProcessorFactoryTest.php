<?php

namespace Tests\Unit\Import\Services;

use Ksfraser\FaBankImport\Import\Services\TransactionProcessorFactory;
use Ksfraser\FaBankImport\Import\Exceptions\ProcessorNotFoundException;
use PHPUnit\Framework\TestCase;

class TransactionProcessorFactoryTest extends TestCase
{
    private TransactionProcessorFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new TransactionProcessorFactory();
    }

    /**
     * Test getting supported types.
     *
     * @test
     */
    public function testGetSupportedTypes(): void
    {
        $types = $this->factory->getSupportedTypes();

        $this->assertContains('SP', $types);
        $this->assertContains('CU', $types);
        $this->assertContains('BT', $types);
    }

    /**
     * Test checking if type is supported.
     *
     * @test
     */
    public function testIsSupported(): void
    {
        $this->assertTrue($this->factory->isSupported('CU'));
        $this->assertTrue($this->factory->isSupported('SP'));
        $this->assertFalse($this->factory->isSupported('XX'));
    }

    /**
     * Test creating processor for unknown partner type throws exception.
     *
     * @test
     */
    public function testUnknownPartnerTypeThrowsException(): void
    {
        $this->expectException(ProcessorNotFoundException::class);

        $this->factory->create('UNKNOWN');
    }

    /**
     * Test empty partner type throws exception.
     *
     * @test
     */
    public function testEmptyPartnerTypeThrowsException(): void
    {
        $this->expectException(ProcessorNotFoundException::class);

        $this->factory->create('');
    }

    /**
     * Test registering custom processor.
     *
     * @test
     */
    public function testRegisteringCustomProcessor(): void
    {
        $this->assertFalse($this->factory->isSupported('CUSTOM'));

        $this->factory->register('CUSTOM', 'Ksfraser\\FaBankImport\\Import\\Strategies\\CustomProcessor');

        $this->assertTrue($this->factory->isSupported('CUSTOM'));
    }
}
