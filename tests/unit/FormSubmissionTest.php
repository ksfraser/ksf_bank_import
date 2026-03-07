<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\Superglobals\FormSubmission;
use Ksfraser\Superglobals\ParameterProvider;

class FormSubmissionTest extends TestCase
{
    public function testHasUploadReturnsTrueWhenUploadPresent()
    {
        $parameterProvider = $this->createMock(ParameterProvider::class);
        $parameterProvider->method('get')->willReturnCallback(function($key) {
            return $key === 'upload' ? '1' : null;
        });

        $formSubmission = new FormSubmission($parameterProvider);
        $this->assertTrue($formSubmission->hasUpload());
    }

    public function testHasUploadReturnsFalseWhenUploadNotPresent()
    {
        $parameterProvider = $this->createMock(ParameterProvider::class);
        $parameterProvider->method('get')->willReturn(null);

        $formSubmission = new FormSubmission($parameterProvider);
        $this->assertFalse($formSubmission->hasUpload());
    }

    public function testHasImportReturnsTrueWhenImportPresent()
    {
        $parameterProvider = $this->createMock(ParameterProvider::class);
        $parameterProvider->method('get')->willReturnCallback(function($key) {
            return $key === 'import' ? '1' : null;
        });

        $formSubmission = new FormSubmission($parameterProvider);
        $this->assertTrue($formSubmission->hasImport());
    }

    public function testHasImportReturnsFalseWhenImportNotPresent()
    {
        $parameterProvider = $this->createMock(ParameterProvider::class);
        $parameterProvider->method('get')->willReturn(null);

        $formSubmission = new FormSubmission($parameterProvider);
        $this->assertFalse($formSubmission->hasImport());
    }

    public function testGetStateReturnsStateValue()
    {
        $parameterProvider = $this->createMock(ParameterProvider::class);
        $parameterProvider->method('get')->willReturnCallback(function($key) {
            return $key === 'state' ? 'upload' : null;
        });

        $formSubmission = new FormSubmission($parameterProvider);
        $this->assertEquals('upload', $formSubmission->getState());
    }

    public function testGetParserReturnsParserValue()
    {
        $parameterProvider = $this->createMock(ParameterProvider::class);
        $parameterProvider->method('get')->willReturnCallback(function($key) {
            return $key === 'parser' ? 'QFX' : null;
        });

        $formSubmission = new FormSubmission($parameterProvider);
        $this->assertEquals('QFX', $formSubmission->getParser());
    }

    public function testGetBankAccountReturnsBankAccountValue()
    {
        $parameterProvider = $this->createMock(ParameterProvider::class);
        $parameterProvider->method('get')->willReturnCallback(function($key) {
            return $key === 'bank_account' ? '123' : null;
        });

        $formSubmission = new FormSubmission($parameterProvider);
        $this->assertEquals('123', $formSubmission->getBankAccount());
    }
}