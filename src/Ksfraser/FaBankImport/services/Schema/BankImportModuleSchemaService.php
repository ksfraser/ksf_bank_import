<?php

namespace Ksfraser\FaBankImport\Services\Schema;

use Ksfraser\FaBankImport\Shared\Repositories\BankAccountMappingRepository;

/**
 * Centralized module-level schema maintenance.
 *
 * Runs idempotent, non-destructive ensure calls for all module-owned tables.
 * 
 * Phase 4 Refactoring: Uses BankAccountMappingRepository for OFX identifier
 * lookups instead of direct database queries, following SRP.
 */
class BankImportModuleSchemaService
{
    /**
     * @var BankAccountMappingRepository
     */
    private $bankAccountMappingRepository;

    /**
     * Constructor
     * 
     * @param BankAccountMappingRepository|null $bankAccountMappingRepository Optional repository instance
     */
    public function __construct(?BankAccountMappingRepository $bankAccountMappingRepository = null)
    {
        $this->bankAccountMappingRepository = $bankAccountMappingRepository ?: new BankAccountMappingRepository();
    }

    /**
     * Ensure schema drift repairs for all module tables.
     *
     * @return array<string, bool>
     */
    public function ensureAll(): array
    {
		require_once(__DIR__ . '/../../../../../class.bi_statements.php');
		(new \bi_statements_model())->ensure_schema();

		require_once(__DIR__ . '/../../../../../class.bi_transactions.php');
		(new \bi_transactions_model())->ensure_schema();

		require_once(__DIR__ . '/../../../../../class.bi_partners_data.php');
		(new \bi_partners_data())->ensure_schema();

		require_once(__DIR__ . '/../../../../../class.bi_transfer_matches.php');
		(new \bi_transfer_matches_model())->ensure_schema();

        require_once(__DIR__ . '/BiConfigSchemaInstaller.php');
        $configSchemaInstaller = new BiConfigSchemaInstaller(
            'db_query',
            TB_PREF
        );
        $configSchemaInstaller->ensureTables();

        require_once(__DIR__ . '/BiUploadedFilesSchemaInstaller.php');
        $uploadedFilesSchemaInstaller = new BiUploadedFilesSchemaInstaller(
            'db_query',
            TB_PREF
        );
        $uploadedFilesSchemaInstaller->ensureTables();

        require_once(__DIR__ . '/BiBankAccountsSchemaInstaller.php');
        $bankAccountsSchemaInstaller = new BiBankAccountsSchemaInstaller(
            'db_query',
            'db_escape',
            'db_num_rows',
            TB_PREF
        );
        $bankAccountsSchemaInstaller->ensureTable();

        require_once(__DIR__ . '/../LegacyBankAccountsMigrator.php');
        $migrator = new \Ksfraser\FaBankImport\Service\LegacyBankAccountsMigrator(
            'db_query',
            'db_escape',
            'db_num_rows',
            TB_PREF
        );
        $migrator->migrate();

        return [
            'bi_statements' => true,
            'bi_transactions' => true,
            'bi_partners_data' => true,
            'bi_transfer_matches' => true,
            'bi_config' => true,
            'bi_uploaded_files' => true,
            'bi_bank_accounts' => true,
            'legacy_bank_accounts_migrated' => true,
        ];
    }

    /**
     * Get mapping by OFX identifiers using Repository
     * 
     * Phase 4 Refactoring: Delegates to BankAccountMappingRepository
     * for all OFX identifier lookups.
     * 
     * @param string|null $bankid OFX BANKID
     * @param string|null $acctid OFX ACCTID
     * @param string|null $intu_bid Intuit BID
     * @return \Ksfraser\FaBankImport\Shared\Entities\BankAccountMapping|null
     */
    public function getBankAccountMappingByOFXIdentifiers(?string $bankid, ?string $acctid, ?string $intu_bid)
    {
        try {
            return $this->bankAccountMappingRepository->findByOFXIdentifiers($bankid, $acctid, $intu_bid);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get all mappings for a FA bank account using Repository
     * 
     * @param int $faAccountId The FA bank account ID
     * @return array Array of BankAccountMapping entities
     */
    public function getMappingsForFABankAccount(int $faAccountId): array
    {
        try {
            return $this->bankAccountMappingRepository->findByFABankAccountId($faAccountId);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Count total bank account mappings
     * 
     * @return int Total count of mappings
     */
    public function countBankAccountMappings(): int
    {
        try {
            return $this->bankAccountMappingRepository->countAll();
        } catch (\Exception $e) {
            return 0;
        }
    }
}
