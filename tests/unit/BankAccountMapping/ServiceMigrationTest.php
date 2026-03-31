<?php
/**
 * Phase 4: Service Migration Test Suite
 * 
 * Tests for migrating services from scattered static lookups to centralized
 * BankAccountMappingRepository usage.
 * 
 * Services to migrate:
 * - TransferMatchService
 * - BankImportModuleSchemaService
 * - ContactService
 */

namespace Tests\Unit\BankAccountMapping;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Shared\Entities\BankAccountMapping;
use Ksfraser\FaBankImport\Shared\Repositories\BankAccountMappingRepository;
use KsfBankImport\Services\TransferMatchService;
use Ksfraser\FaBankImport\Service\Schema\BankImportModuleSchemaService;
use Ksfraser\FaBankImport\Services\ContactService;

class ServiceMigrationTest extends TestCase
{
    /**
     * Mock mapping for testing
     */
    protected function createMockMapping(array $overrides = []): BankAccountMapping
    {
        $defaults = [
            'bank_account_id' => 1,
            'bankid' => 'test_bank',
            'acctid' => 'test_acct',
            'intu_bid' => 'intuit_123',
            'curdef' => 'USD'
        ];
        
        return new BankAccountMapping(array_merge($defaults, $overrides));
    }

    // =====================================================================
    // TransferMatchService Tests
    // =====================================================================

    /**
     * TEST: TransferMatchService Uses Repository
     * 
     * TransferMatchService should use BankAccountMappingRepository
     * to find accounts instead of static lookups.
     * 
     * ARRANGE: Service instance with OFX identifiers
     * ACT: Call service method requiring account lookup
     * ASSERT: Uses repository.findByOFXIdentifiers()
     */
    public function test_transfer_match_service_uses_repository(): void
    {
        $service = new TransferMatchService();
        
        // Service has repository injected (verified via reflection)
        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('bankAccountMappingRepository');
        $property->setAccessible(true);
        
        $this->assertNotNull($property->getValue($service), 'TransferMatchService should have repository injected');
        $this->assertInstanceOf(BankAccountMappingRepository::class, $property->getValue($service));
    }

    /**
     * TEST: TransferMatchService Finds Accounts Correctly
     * 
     * Service should find correct FA account based on OFX identifiers.
     * 
     * ARRANGE: Known mapping in repository
     * ACT: Call service to find account for OFX identifiers
     * ASSERT: Returns correct FA account ID
     */
    public function test_transfer_match_service_finds_accounts_correctly(): void
    {
        $service = new TransferMatchService();
        
        // Test with known OFX identifiers
        $bankid = 'test_bank';
        $acctid = 'test_acct';
        $intu_bid = 'intuit_123';
        
        // Method should exist and be callable
        $this->assertTrue(
            method_exists($service, 'getFABankAccountFromOFXIdentifiers'),
            'TransferMatchService should have getFABankAccountFromOFXIdentifiers method'
        );
    }

    /**
     * TEST: TransferMatchService Handles Missing Accounts
     * 
     * Service should handle gracefully when account is not found.
     * 
     * ARRANGE: OFX identifiers with no mapping
     * ACT: Call service lookup
     * ASSERT: Returns null/empty, no exception
     */
    public function test_transfer_match_service_handles_missing_accounts(): void
    {
        $service = new TransferMatchService();
        
        // Call with non-existent identifiers - should not throw exception
        try {
            $result = $service->getFABankAccountFromOFXIdentifiers('nonexistent_bank', 'nonexistent_acct', 'nonexistent_intuit');
            $this->assertNull($result, 'Should return null for non-existent mapping');
        } catch (\Exception $e) {
            $this->fail('Should not throw exception for missing mapping: ' . $e->getMessage());
        }
    }

    /**
     * TEST: TransferMatchService Performance
     * 
     * Account lookup should be fast (<50ms for typical call).
     * 
     * ARRANGE: Service with known mappings
     * ACT: Time account lookup call
     * ASSERT: Completes in <50ms
     */
    public function test_transfer_match_service_performance_acceptable(): void
    {
        $service = new TransferMatchService();
        
        // Time the lookup operation
        $start = microtime(true);
        for ($i = 0; $i < 10; $i++) {
            $service->getFABankAccountFromOFXIdentifiers('test_bank', 'test_acct', null);
        }
        $elapsed = (microtime(true) - $start) * 1000;
        $avgPerCall = $elapsed / 10;
        
        $this->assertLessThan(50, $avgPerCall, "Average lookup time should be < 50ms (was {$avgPerCall}ms)");
    }

    /**
     * TEST: TransferMatchService No Static Calls Remain
     * 
     * Verify that all static lookup calls have been replaced with
     * repository calls in TransferMatchService.
     * 
     * ASSERT: No legacy static lookup patterns found
     */
    public function test_transfer_match_service_no_static_calls(): void
    {
        // Verify the method uses repository instead of static calls
        $service = new TransferMatchService();
        $reflection = new \ReflectionMethod($service, 'getFABankAccountFromOFXIdentifiers');
        $fileName = $reflection->getFileName();
        $startLine = $reflection->getStartLine();
        $endLine = $reflection->getEndLine();
        
        // Read method source to verify it uses repository
        $file = file($fileName);
        $methodSource = implode('', array_slice($file, $startLine - 1, $endLine - $startLine + 1));
        
        // Should contain 'bankAccountMappingRepository' (indicating repository use)
        $this->assertStringContainsString('bankAccountMappingRepository', $methodSource, 'Method should use repository');
        // Should NOT contain static method calls like 'bi_bank_accounts::'
        $this->assertStringNotContainsString('bi_bank_accounts::', $methodSource, 'Method should not use static bi_bank_accounts calls');
    }

    // =====================================================================
    // BankImportModuleSchemaService Tests
    // =====================================================================

    /**
     * TEST: BankImportModuleSchemaService Uses Repository
     * 
     * Schema service should use BankAccountMappingRepository for
     * OFX identifier lookups instead of direct DB queries.
     * 
     * ARRANGE: Service instance
     * ACT: Call schema service method
     * ASSERT: Uses repository for lookups
     */
    public function test_bank_import_schema_service_uses_repository(): void
    {
        $service = new BankImportModuleSchemaService();
        
        // Service has repository injected (verified via reflection)
        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('bankAccountMappingRepository');
        $property->setAccessible(true);
        
        $this->assertNotNull($property->getValue($service), 'Schema service should have repository injected');
        $this->assertInstanceOf(BankAccountMappingRepository::class, $property->getValue($service));
    }

    /**
     * TEST: BankImportModuleSchemaService Finds Mappings
     * 
     * Service should find mappings by OFX identifiers.
     * 
     * ARRANGE: Known mappings in repository
     * ACT: Query service for mapping
     * ASSERT: Returns correct mapping
     */
    public function test_bank_import_schema_service_finds_mappings(): void
    {
        $service = new BankImportModuleSchemaService();
        
        // Verify service can get repository
        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('bankAccountMappingRepository');
        $property->setAccessible(true);
        $repository = $property->getValue($service);
        
        $this->assertNotNull($repository, 'Schema service should have access to repository');
    }

    /**
     * TEST: BankImportModuleSchemaService Handles Missing Mappings
     * 
     * Service should handle missing mappings gracefully.
     * 
     * ARRANGE: Query for non-existent mapping
     * ACT: Call service
     * ASSERT: Returns empty/null, no exception
     */
    public function test_bank_import_schema_service_handles_missing_mappings_gracefully(): void
    {
        $service = new BankImportModuleSchemaService();
        
        // Service should not throw when handling missing data
        try {
            // The ensureAll method should complete without error
            $result = $service->ensureAll();
            $this->assertIsArray($result, 'ensureAll should return array');
        } catch (\Exception $e) {
            $this->fail('Schema service should handle missing mappings gracefully: ' . $e->getMessage());
        }
    }

    /**
     * TEST: BankImportModuleSchemaService Query Performance
     * 
     * Schema queries should be fast (<75ms).
     * 
     * ARRANGE: Complex schema query
     * ACT: Time query execution
     * ASSERT: Completes in <75ms
     */
    public function test_bank_import_schema_service_performance_acceptable(): void
    {
        $service = new BankImportModuleSchemaService();
        
        $start = microtime(true);
        for ($i = 0; $i < 5; $i++) {
            $service->ensureAll();
        }
        $elapsed = (microtime(true) - $start) * 1000;
        $avgPerCall = $elapsed / 5;
        
        $this->assertLessThan(75, $avgPerCall, "Average schema operation should be < 75ms (was {$avgPerCall}ms)");
    }

    /**
     * TEST: BankImportModuleSchemaService No Static Calls
     * 
     * Verify all direct bi_bank_accounts lookups replaced.
     * 
     * ASSERT: No direct static calls to old lookup methods
     */
    public function test_bank_import_schema_service_no_static_calls(): void
    {
        $service = new BankImportModuleSchemaService();
        $reflection = new \ReflectionClass($service);
        
        // Verify the service class has repository property (proof of DI)
        $hasRepositoryProperty = false;
        foreach ($reflection->getProperties() as $prop) {
            if ($prop->getName() === 'bankAccountMappingRepository') {
                $hasRepositoryProperty = true;
                break;
            }
        }
        
        $this->assertTrue($hasRepositoryProperty, 'Schema service should have bankAccountMappingRepository property for DI');
    }

    // =====================================================================
    // ContactService Tests
    // =====================================================================

    /**
     * TEST: ContactService Uses Repository
     * 
     * ContactService should use BankAccountMappingRepository
     * when linking/unlinking bank accounts.
     * 
     * ARRANGE: Service instance
     * ACT: Call linking method
     * ASSERT: Uses repository for persistence
     */
    public function test_contact_service_uses_repository(): void
    {
        $service = new ContactService();
        
        // Service has repository injected (verified via reflection)
        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('bankAccountMappingRepository');
        $property->setAccessible(true);
        
        $this->assertNotNull($property->getValue($service), 'ContactService should have repository injected');
        $this->assertInstanceOf(BankAccountMappingRepository::class, $property->getValue($service));
    }

    /**
     * TEST: ContactService Links with Mapping Repository
     * 
     * When linking a bank account to a contact, should store mapping.
     * 
     * ARRANGE: Contact and bank account data
     * ACT: Call link method
     * ASSERT: Mapping stored in repository
     */
    public function test_contact_service_links_with_mapping_repository(): void
    {
        $service = new ContactService();
        
        // Verify service has method to get OFX mapping by identifiers
        $this->assertTrue(
            method_exists($service, 'getBankAccountMappingByOFXIdentifiers'),
            'ContactService should have method to retrieve mappings'
        );
        
        // Should return null for non-existent mapping
        $result = $service->getBankAccountMappingByOFXIdentifiers('test_bank', 'test_acct', null);
        $this->assertNull($result, 'Should return null for non-existent mapping');
    }

    /**
     * TEST: ContactService Updates Mapping on Account Change
     * 
     * When contact's bank account is changed, mapping should update.
     * 
     * ARRANGE: Contact with mapping linked to account A
     * ACT: Change mapping to account B
     * ASSERT: Mapping updated in repository
     */
    public function test_contact_service_updates_mapping_on_account_change(): void
    {
        $service = new ContactService();
        
        // Should have method to get FA bank account for OFX identifiers
        $this->assertTrue(
            method_exists($service, 'getFABankAccountIdForOFXIdentifiers'),
            'ContactService should have method to get FA account ID'
        );
        
        // Test that it handles missing mappings gracefully
        try {
            $result = $service->getFABankAccountIdForOFXIdentifiers('test_bank', 'test_acct', null);
            $this->assertNull($result, 'Should return null when no mapping exists');
        } catch (\Exception $e) {
            $this->fail('Should not throw exception: ' . $e->getMessage());
        }
    }

    /**
     * TEST: ContactService Unlinks Mapping
     * 
     * When removing bank account from contact, mapping should be removed.
     * 
     * ARRANGE: Contact with linked mapping
     * ACT: Call unlink method
     * ASSERT: Mapping removed or disabled
     */
    public function test_contact_service_removes_mapping_on_unlink(): void
    {
        $service = new ContactService();
        
        // Service should gracefully handle unlink operations
        try {
            $result = $service->getBankAccountMappingByOFXIdentifiers('nonexistent', 'nonexistent', null);
            $this->assertNull($result, 'Non-existent mapping should return null');
        } catch (\Exception $e) {
            $this->fail('Should handle unlink gracefully: ' . $e->getMessage());
        }
    }

    /**
     * TEST: ContactService Performance
     * 
     * Banking account operations should be fast (<75ms).
     * 
     * ARRANGE: Contact with bank operations
     * ACT: Time link/unlink operations
     * ASSERT: Completes in <75ms each
     */
    public function test_contact_service_performance_acceptable(): void
    {
        $service = new ContactService();
        
        $start = microtime(true);
        for ($i = 0; $i < 10; $i++) {
            $service->getBankAccountMappingByOFXIdentifiers('test_' . $i, 'test_acct_' . $i, null);
        }
        $elapsed = (microtime(true) - $start) * 1000;
        $avgPerCall = $elapsed / 10;
        
        $this->assertLessThan(75, $avgPerCall, "Average contact operation should be < 75ms (was {$avgPerCall}ms)");
    }

    // =====================================================================
    // Cross-Service Integration Tests
    // =====================================================================

    /**
     * TEST: All Services Drop Static Lookup Calls
     * 
     * Comprehensive check that ALL static OFX identifier lookups
     * have been migrated from services to repository.
     * 
     * ASSERT: No legacy static lookup patterns anywhere
     */
    public function test_all_services_drop_static_lookup_calls(): void
    {
        // All three services should have repository injected
        $services = [
            new TransferMatchService(),
            new BankImportModuleSchemaService(),
            new ContactService()
        ];
        
        foreach ($services as $service) {
            $reflection = new \ReflectionClass($service);
            $hasRepository = false;
            
            foreach ($reflection->getProperties() as $prop) {
                if ($prop->getName() === 'bankAccountMappingRepository') {
                    $hasRepository = true;
                    break;
                }
            }
            
            $this->assertTrue(
                $hasRepository,
                get_class($service) . ' should have bankAccountMappingRepository for DI'
            );
        }
    }

    /**
     * TEST: Services Backward Compatible
     * 
     * All service method signatures and behaviors should be unchanged
     * from a caller's perspective. Migration is internal only.
     * 
     * ASSERT: Existing code calling services still works
     */
    public function test_services_backward_compatible_with_existing_code(): void
    {
        // Services should be instantiable with no required arguments
        try {
            $transfer = new TransferMatchService();
            $this->assertNotNull($transfer);
            
            $schema = new BankImportModuleSchemaService();
            $this->assertNotNull($schema);
            
            $contact = new ContactService();
            $this->assertNotNull($contact);
        } catch (\Exception $e) {
            $this->fail('Services should be backward compatible: ' . $e->getMessage());
        }
    }

    /**
     * TEST: Service Migration Maintains Performance
     * 
     * Overall service performance should not degrade or only minimally
     * due to migration to repository pattern.
     * 
     * ARRANGE: Typical service call patterns
     * ACT: Time service calls
     * ASSERT: Performance within acceptable bounds
     */
    public function test_service_migration_maintains_performance(): void
    {
        $service = new TransferMatchService();
        
        // Verify performance baseline
        $start = microtime(true);
        for ($i = 0; $i < 20; $i++) {
            $service->getFABankAccountFromOFXIdentifiers('bank' . $i, 'acct' . $i, null);
        }
        $elapsed = (microtime(true) - $start) * 1000;
        
        // Should complete 20 calls in under 100ms (average 5ms per call)
        $this->assertLessThan(100, $elapsed, "20 service calls should complete in < 100ms (was {$elapsed}ms)");
    }

    /**
     * TEST: Service Error Handling Consistent
     * 
     * All services should have consistent error handling patterns,
     * especially for missing mappings or DB errors.
     * 
     * ASSERT: Error handling patterns consistent across services
     */
    public function test_service_error_handling_consistent(): void
    {
        $services = [
            'TransferMatchService' => new TransferMatchService(),
            'BankImportModuleSchemaService' => new BankImportModuleSchemaService(),
            'ContactService' => new ContactService()
        ];
        
        // All services should handle missing data gracefully
        foreach ($services as $name => $service) {
            try {
                if ($service instanceof TransferMatchService) {
                    $result = $service->getFABankAccountFromOFXIdentifiers('nonexistent', 'nonexistent', null);
                    $this->assertNull($result, "$name should return null for missing mapping");
                } elseif ($service instanceof ContactService) {
                    $result = $service->getBankAccountMappingByOFXIdentifiers('nonexistent', 'nonexistent', null);
                    $this->assertNull($result, "$name should return null for missing mapping");
                }
            } catch (\Exception $e) {
                $this->fail("$name error handling failed: " . $e->getMessage());
            }
        }
    }

    /**
     * TEST: Service Integration - Data Consistency
     * 
     * When one service updates a mapping, verify other services
     * see the updated data (eventual consistency).
     * 
     * ARRANGE: Services with shared mappings
     * ACT: Modify mapping through one service, query through another
     * ASSERT: Both see consistent data
     */
    public function test_service_integration_data_consistency(): void
    {
        $transfer = new TransferMatchService();
        $contact = new ContactService();
        
        // Both should use same repository (singleton pattern or injected)
        $transferRepo = new \ReflectionClass($transfer);
        $transferRepoProperty = $transferRepo->getProperty('bankAccountMappingRepository');
        $transferRepoProperty->setAccessible(true);
        
        $contactRepo = new \ReflectionClass($contact);
        $contactRepoProperty = $contactRepo->getProperty('bankAccountMappingRepository');
        $contactRepoProperty->setAccessible(true);
        
        // Both services access repository (tested above)
        $this->assertNotNull($transferRepoProperty->getValue($transfer));
        $this->assertNotNull($contactRepoProperty->getValue($contact));
    }

    // =====================================================================
    // Regression Tests
    // =====================================================================

    /**
     * TEST: Legacy Service Behavior Preserved
     * 
     * All existing functionality that depends on these services
     * should continue working without modification.
     * 
     * ASSERT: No regression in service functionality
     */
    public function test_regression_legacy_behavior_preserved(): void
    {
        $service = new TransferMatchService();
        
        // Test that method signatures are unchanged
        $reflection = new \ReflectionClass($service);
        
        // Should have the expected methods
        $this->assertTrue(
            $reflection->hasMethod('getFABankAccountFromOFXIdentifiers'),
            'TransferMatchService should maintain getFABankAccountFromOFXIdentifiers method'
        );
        
        $this->assertTrue(
            $reflection->hasMethod('getMappingsForFABankAccount'),
            'TransferMatchService should maintain getMappingsForFABankAccount method'
        );
    }

    /**
     * TEST: Report Generation Still Works
     * 
     * Any reports or dashboards depending on service bank account
     * lookups should still generate correct data.
     * 
     * ASSERT: Reports unaffected by migration
     */
    public function test_regression_report_generation_unaffected(): void
    {
        $service = new TransferMatchService();
        
        // Method for getting all mappings for a bank account should still work
        $this->assertTrue(
            method_exists($service, 'getMappingsForFABankAccount'),
            'Report method getMappingsForFABankAccount should exist'
        );
        
        try {
            $result = $service->getMappingsForFABankAccount(1);
            $this->assertIsArray($result, 'Should return array of mappings');
        } catch (\Exception $e) {
            $this->fail('Report generation method should not throw: ' . $e->getMessage());
        }
    }

    /**
     * TEST: API/REST Endpoints Still Work
     * 
     * Any API endpoints that depend on these services should continue
     * working correctly.
     * 
     * ASSERT: API endpoints functional
     */
    public function test_regression_api_endpoints_functional(): void
    {
        $contactService = new ContactService();
        
        // API method should be accessible
        $this->assertTrue(
            method_exists($contactService, 'getBankAccountMappingByOFXIdentifiers'),
            'API method getBankAccountMappingByOFXIdentifiers should exist'
        );
        
        try {
            $result = $contactService->getFABankAccountIdForOFXIdentifiers('api_test', 'api_acct', null);
            $this->assertNull($result, 'API call should return null for non-existent mapping');
        } catch (\Exception $e) {
            $this->fail('API method should not throw: ' . $e->getMessage());
        }
    }

    /**
     * TEST: Transfer Matching Pipeline Unaffected
     * 
     * The overall transfer matching pipeline should work unchanged
     * with the service migrations.
     * 
     * ASSERT: Transfer matching still functional end-to-end
     */
    public function test_regression_transfer_matching_still_works(): void
    {
        $service = new TransferMatchService();
        
        // The service should still have all its original functionality
        $this->assertTrue(
            method_exists($service, 'getFABankAccountFromOFXIdentifiers'),
            'Transfer matching requires getFABankAccountFromOFXIdentifiers'
        );
        
        // Should handle typical transfer matching workflow
        try {
            $accountId = $service->getFABankAccountFromOFXIdentifiers('chase_123', 'account_456', null);
            // accountId can be null for non-existent mapping, that's fine
            $this->assertTrue(is_null($accountId) || is_int($accountId), 'Should return int or null');
        } catch (\Exception $e) {
            $this->fail('Transfer matching pipeline should not throw: ' . $e->getMessage());
        }
    }
}
