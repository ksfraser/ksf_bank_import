<?php

namespace Tests\Unit\Import\Services;

use Ksfraser\FaBankImport\Import\Services\ProcessTransactionValidator;
use Ksfraser\FaBankImport\Import\Results\ValidationResult;
use PHPUnit\Framework\TestCase;

class ProcessTransactionValidatorTest extends TestCase
{
    private ProcessTransactionValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ProcessTransactionValidator();
    }

    /**
     * Test valid POST data passes validation.
     *
     * @test
     */
    public function testValidPostData(): void
    {
        $post = [
            'ProcessTransaction' => ['1' => 'true'],
            'partnerType' => ['1' => 'CU'],
            'partnerId' => ['1' => '100'],
            'cids' => ['1' => '201,202,203'],
        ];

        $result = $this->validator->validatePostData($post, 1);

        $this->assertTrue($result->isValid());
    }

    /**
     * Test missing ProcessTransaction action.
     *
     * @test
     */
    public function testMissingProcessTransactionAction(): void
    {
        $post = [];

        $result = $this->validator->validatePostData($post, 1);

        $this->assertFalse($result->isValid());
    }

    /**
     * Test missing partner type.
     *
     * @test
     */
    public function testMissingPartnerType(): void
    {
        $post = [
            'ProcessTransaction' => ['1' => 'true'],
            'partnerId' => '100',
        ];

        $result = $this->validator->validatePostData($post, 1);

        $this->assertFalse($result->isValid());
        $this->assertNotEmpty($result->getFieldErrors('partnerType'));
    }

    /**
     * Test missing partner ID.
     *
     * @test
     */
    public function testMissingPartnerId(): void
    {
        $post = [
            'ProcessTransaction' => ['1' => 'true'],
            'partnerType' => 'CU',
        ];

        $result = $this->validator->validatePostData($post, 1);

        $this->assertFalse($result->isValid());
        $this->assertNotEmpty($result->getFieldErrors('partnerId'));
    }

    /**
     * Test valid partner type validation.
     *
     * @test
     */
    public function testValidPartnerType(): void
    {
        $result = $this->validator->validatePartnerType('CU', ['CU', 'SP', 'DE']);

        $this->assertTrue($result->isValid());
    }

    /**
     * Test invalid partner type validation.
     *
     * @test
     */
    public function testInvalidPartnerType(): void
    {
        $result = $this->validator->validatePartnerType('XX', ['CU', 'SP', 'DE']);

        $this->assertFalse($result->isValid());
        $this->assertNotEmpty($result->getFieldErrors('partnerType'));
    }

    /**
     * Test collection IDs validation.
     *
     * @test
     */
    public function testValidCollectionIds(): void
    {
        $result = $this->validator->validateCollectionIds('100,101,102');

        $this->assertTrue($result->isValid());
    }

    /**
     * Test invalid collection IDs.
     *
     * @test
     */
    public function testInvalidCollectionIds(): void
    {
        $result = $this->validator->validateCollectionIds('100,abc,102');

        $this->assertFalse($result->isValid());
        $this->assertNotEmpty($result->getFieldErrors('collectionIds'));
    }

    /**
     * Test empty collection IDs is valid.
     *
     * @test
     */
    public function testEmptyCollectionIdsIsValid(): void
    {
        $result = $this->validator->validateCollectionIds('');

        $this->assertTrue($result->isValid());
    }
}
