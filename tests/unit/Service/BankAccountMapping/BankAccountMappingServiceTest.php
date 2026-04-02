<?php

namespace Tests\Unit\Service\BankAccountMapping;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Service\BankAccountMapping\BankAccountMappingService;
use Ksfraser\FaBankImport\Shared\Repositories\BankAccountMappingRepository;

/**
 * Test BankAccountMappingService - delegates bank account mapping queries to repository
 * 
 * Responsibility: Repository->Query layer for bank account mappings
 * Changes when: Bank account mapping query logic changes
 */
class BankAccountMappingServiceTest extends TestCase
{
    private BankAccountMappingService $service;

    protected function setUp(): void
    {
        $this->service = new BankAccountMappingService();
    }

    /**
     * @test
     */
    public function has_method_get_bank_account_mapping_by_ofx_identifiers(): void
    {
        $this->assertTrue(
            method_exists($this->service, 'getBankAccountMappingByOFXIdentifiers'),
            'Service should have getBankAccountMappingByOFXIdentifiers method'
        );
    }

    /**
     * @test
     */
    public function get_bank_account_mapping_by_ofx_identifiers_returns_null_or_mapping(): void
    {
        // With empty identifiers, should handle gracefully
        $result = $this->service->getBankAccountMappingByOFXIdentifiers(null, null, null);
        
        // Should return null or an object, not throw
        $this->assertTrue($result === null || is_object($result));
    }

    /**
     * @test
     */
    public function has_method_get_mappings_for_fa_bank_account(): void
    {
        $this->assertTrue(
            method_exists($this->service, 'getMappingsForFABankAccount'),
            'Service should have getMappingsForFABankAccount method'
        );
    }

    /**
     * @test
     */
    public function get_mappings_for_fa_bank_account_returns_array(): void
    {
        $result = $this->service->getMappingsForFABankAccount(1);
        
        $this->assertIsArray($result, 'Should return array');
    }

    /**
     * @test
     */
    public function has_method_count_bank_account_mappings(): void
    {
        $this->assertTrue(
            method_exists($this->service, 'countBankAccountMappings'),
            'Service should have countBankAccountMappings method'
        );
    }

    /**
     * @test
     */
    public function count_bank_account_mappings_returns_integer(): void
    {
        $result = $this->service->countBankAccountMappings();
        
        $this->assertIsInt($result, 'Should return integer');
        $this->assertGreaterThanOrEqual(0, $result, 'Count should be non-negative');
    }

    /**
     * @test
     */
    public function accepts_repository_injection(): void
    {
        $mockRepo = $this->createMock(BankAccountMappingRepository::class);
        $customService = new BankAccountMappingService($mockRepo);
        
        $this->assertInstanceOf(BankAccountMappingService::class, $customService);
    }

    /**
     * @test
     */
    public function creates_default_repository_when_none_provided(): void
    {
        $service = new BankAccountMappingService();
        
        // Should not throw, should have working methods
        $this->assertInstanceOf(BankAccountMappingService::class, $service);
    }
}

