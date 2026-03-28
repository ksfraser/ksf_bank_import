<?php

namespace Tests\Unit\Import\Results;

use Ksfraser\FaBankImport\Import\Results\ValidationResult;
use PHPUnit\Framework\TestCase;

class ValidationResultTest extends TestCase
{
    /**
     * Test passing validation result.
     *
     * @test
     */
    public function testPassingValidation(): void
    {
        $result = ValidationResult::valid();

        $this->assertTrue($result->isValid());
        $this->assertTrue($result->isSuccess());
        $this->assertCount(0, $result->getErrors());
    }

    /**
     * Test failing validation result.
     *
     * @test
     */
    public function testFailingValidation(): void
    {
        $result = ValidationResult::invalid('Validation failed');

        $this->assertFalse($result->isValid());
        $this->assertFalse($result->isSuccess());
        $this->assertCount(1, $result->getErrors());
    }

    /**
     * Test field-level errors.
     *
     * @test
     */
    public function testFieldLevelErrors(): void
    {
        $result = ValidationResult::valid();

        $result->addFieldError('email', 'Invalid format')
            ->addFieldError('email', 'Already exists')
            ->addFieldError('password', 'Too short');

        $this->assertFalse($result->isValid());
        $this->assertCount(2, $result->getFieldErrors('email'));
        $this->assertCount(1, $result->getFieldErrors('password'));
        $this->assertEquals(3, $result->getViolationCount());
    }

    /**
     * Test recording validation rules.
     *
     * @test
     */
    public function testRuleChecks(): void
    {
        $result = ValidationResult::valid()
            ->recordRuleCheck('email_required', true)
            ->recordRuleCheck('email_format', false)
            ->recordRuleCheck('password_length', true);

        $rules = $result->getCheckedRules();
        $this->assertTrue($rules['email_required']);
        $this->assertFalse($rules['email_format']);
        $this->assertTrue($rules['password_length']);
    }

    /**
     * Test violation count with general and field errors.
     *
     * @test
     */
    public function testViolationCount(): void
    {
        $result = ValidationResult::invalid('General error')
            ->addFieldError('field1', 'Error 1')
            ->addFieldError('field1', 'Error 2')
            ->addFieldError('field2', 'Error 3');

        // 1 general + 3 field errors
        $this->assertEquals(4, $result->getViolationCount());
    }
}
