<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Service\AccountMappingResolver;

class AccountMappingResolverTest extends TestCase
{
    public function testResolveReturnsCorrectMappings()
    {
        $detected_account = ['abc' => '123'];
        $resolved_bank_account = ['abc' => 42];
        $remember_mapping = ['abc' => 1];
        $multistatements = [];
        $uploaded_file_ids = [];
        $pending = ['unresolved' => ['123' => [0]]];

        // Mock global functions and dependencies
        // get_bank_account, bi_bank_accounts_upsert, collect_detected_identity_meta, FileUploadService, DetectedAccountAssociationKey
        // For brevity, assume get_bank_account returns ['bank_account_number' => '987654']
        // bi_bank_accounts_upsert and FileUploadService::create() are no-ops
        // DetectedAccountAssociationKey::forDetectedAccount returns 'key123'
        // collect_detected_identity_meta returns ['123' => ['acctid' => '123']]

        // Use runkit or uopz for PHP function mocking if available, or refactor for DI
        $result = AccountMappingResolver::resolve(
            $detected_account,
            $resolved_bank_account,
            $remember_mapping,
            $multistatements,
            $uploaded_file_ids,
            $pending
        );
        $this->assertIsArray($result);
        list($detectedToAccountNumber, $rememberedCount) = $result;
        $this->assertEquals(['123' => '987654'], $detectedToAccountNumber);
        $this->assertEquals(1, $rememberedCount);
    }
}
