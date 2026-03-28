<?php

namespace Tests\Unit\Import\Results;

use Ksfraser\FaBankImport\Import\Results\OperationResult;
use PHPUnit\Framework\TestCase;

class OperationResultTest extends TestCase
{
    /**
     * Test successful result creation.
     *
     * @test
     */
    public function testSuccessfulResult(): void
    {
        $result = OperationResult::success(['id' => 123]);

        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isFailure());
        $this->assertEquals(['id' => 123], $result->getData());
    }

    /**
     * Test failure result creation.
     *
     * @test
     */
    public function testFailureResult(): void
    {
        $result = OperationResult::failure('Transaction not found');

        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isFailure());
        $this->assertEquals('Transaction not found', $result->getFirstError());
    }

    /**
     * Test adding multiple errors.
     *
     * @test
     */
    public function testMultipleErrors(): void
    {
        $result = OperationResult::success()
            ->addError('Error 1')
            ->addError('Error 2');

        $this->assertFalse($result->isSuccess());
        $this->assertCount(2, $result->getErrors());
        $this->assertEquals('Error 1', $result->getFirstError());
    }

    /**
     * Test adding warnings.
     *
     * @test
     */
    public function testWarnings(): void
    {
        $result = OperationResult::success()
            ->addWarning('Warning 1')
            ->addWarning('Warning 2');

        $this->assertCount(2, $result->getWarnings());
    }

    /**
     * Test context management.
     *
     * @test
     */
    public function testContext(): void
    {
        $result = OperationResult::success(null, ['key1' => 'value1'])
            ->addContext('key2', 'value2');

        $this->assertEquals('value1', $result->getContext('key1'));
        $this->assertEquals('value2', $result->getContext('key2'));
        $this->assertArrayHasKey('key1', $result->getContext());
    }

    /**
     * Test chaining methods.
     *
     * @test
     */
    public function testChaining(): void
    {
        $result = OperationResult::success()
            ->addError('Error')
            ->addWarning('Warning')
            ->addContext('ctx', 'val')
            ->setData(['test' => true]);

        $this->assertEquals(['test' => true], $result->getData());
        $this->assertFalse($result->isSuccess()); // Has errors now
    }
}
