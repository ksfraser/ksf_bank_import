<?php

namespace Tests\Unit\Import\Exceptions;

use Ksfraser\FaBankImport\Import\Exceptions\ImportException;
use PHPUnit\Framework\TestCase;

class ImportExceptionTest extends TestCase
{
    /**
     * Test creating exception with basic message and code.
     *
     * @test
     */
    public function testBasicExceptionCreation(): void
    {
        $exception = new ImportException('Test error', 100);

        $this->assertEquals('Test error', $exception->getMessage());
        $this->assertEquals(100, $exception->getCode());
        $this->assertTrue($exception instanceof ImportException);
    }

    /**
     * Test recoverable flag is properly set.
     *
     * @test
     */
    public function testRecoverableFlag(): void
    {
        $recoverable = new ImportException('Error', 1, null, [], true);
        $nonRecoverable = new ImportException('Error', 1, null, [], false);

        $this->assertTrue($recoverable->isRecoverable());
        $this->assertFalse($nonRecoverable->isRecoverable());
    }

    /**
     * Test context data is properly stored.
     *
     * @test
     */
    public function testContextData(): void
    {
        $context = ['transaction_id' => 123, 'amount' => 99.99];
        $exception = new ImportException('Error', 1, null, $context);

        $this->assertEquals($context, $exception->getContext());
        $this->assertEquals(123, $exception->getContext()['transaction_id']);
    }

    /**
     * Test adding context after creation.
     *
     * @test
     */
    public function testAddContext(): void
    {
        $exception = new ImportException('Error');

        $result = $exception->addContext('key1', 'value1')
            ->addContext('key2', 'value2');

        $this->assertSame($exception, $result); // Returns self for chaining
        $this->assertEquals('value1', $exception->getContext()['key1']);
        $this->assertEquals('value2', $exception->getContext()['key2']);
    }

    /**
     * Test exception chaining.
     *
     * @test
     */
    public function testExceptionChaining(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new ImportException('Wrapped error', 1, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
