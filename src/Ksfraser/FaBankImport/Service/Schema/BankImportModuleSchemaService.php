<?php

namespace Ksfraser\FaBankImport\Service\Schema;

/**
 * Centralized module-level schema maintenance.
 *
 * Runs idempotent, non-destructive ensure calls for all module-owned tables.
 */
class BankImportModuleSchemaService
{
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
}
