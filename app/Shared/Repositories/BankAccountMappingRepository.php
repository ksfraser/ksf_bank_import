<?php
namespace Ksfraser\FaBankImport\Shared\Repositories;

use Ksfraser\FaBankImport\Shared\Entities\BankAccountMapping;

/**
 * Repository for BankAccountMapping operations
 *
 * Handles all CRUD operations for bank account mappings (OFX identifiers to FA bank accounts).
 * Provides lookup methods by OFX identifiers, Intuit BID, and FA bank account ID.
 */
class BankAccountMappingRepository
{
    private const TABLE_NAME = 'bi_bank_accounts';
    
    /**
     * Get full table name with prefix
     */
    private static function getTableName(): string
    {
        return TB_PREF . self::TABLE_NAME;
    }

    /**
     * Check if the bank accounts mapping table exists
     */
    public static function tableExists(): bool
    {
        $table = self::getTableName();
        $res = @db_query('SHOW TABLES LIKE ' . db_escape($table), 'Could not check bank accounts table');
        return is_object($res) && db_num_rows($res) > 0;
    }

    /**
     * Create a new BankAccountMapping from entity data
     */
    public static function create(array $data): BankAccountMapping
    {
        return new BankAccountMapping($data);
    }

    /**
     * Find a mapping by ID
     * 
     * @param int $id The mapping ID
     * @return BankAccountMapping|null
     */
    public static function findById(int $id): ?BankAccountMapping
    {
        if ($id <= 0 || !self::tableExists()) {
            return null;
        }

        $table = self::getTableName();
        $sql = "SELECT id, bank_account_id, intu_bid, bankid, acctid, accttype, curdef, updated_ts
                FROM `{$table}`
                WHERE id=" . (int)$id . "
                LIMIT 1";
        
        $res = @db_query($sql, 'Could not fetch bank account mapping');
        if (!is_object($res) || db_num_rows($res) === 0) {
            return null;
        }

        $row = db_fetch($res);
        return is_array($row) ? new BankAccountMapping($row) : null;
    }

    /**
     * Find a mapping by statement data array
     * 
     * Convenience method that accepts a statement array and extracts OFX identifiers.
     * Handles all validation - returns null if identifiers are empty.
     * 
     * @param array $statement The statement data array with bankid, acctid, intu_bid keys
     * @return BankAccountMapping|null
     */
    public static function findByStatementData(array $statement): ?BankAccountMapping
    {
        return self::findByOFXIdentifiers(
            $statement['bankid'] ?? null,
            $statement['acctid'] ?? null,
            $statement['intu_bid'] ?? null
        );
    }

    /**
     * Get FA bank account ID from statement data
     * 
     * Convenience method that returns the FA bank account ID directly.
     * Returns null if no mapping found.
     * 
     * @param array $statement The statement data array
     * @return int|null The FA bank account ID or null
     */
    public static function getFABankAccountIdFromStatement(array $statement): ?int
    {
        $mapping = self::findByStatementData($statement);
        return $mapping ? $mapping->bank_account_id : null;
    }

    /**
     * Find a mapping by OFX identifiers (bankid + acctid + intu_bid)
     * 
     * @param string|null $bankid OFX BANKID
     * @param string|null $acctid OFX ACCTID
     * @param string|null $intu_bid Intuit BID
     * @return BankAccountMapping|null
     */
    public static function findByOFXIdentifiers(
        ?string $bankid = null,
        ?string $acctid = null,
        ?string $intu_bid = null
    ): ?BankAccountMapping {
        if (!self::tableExists()) {
            return null;
        }

        // Normalize inputs
        $bankid = !empty($bankid) ? trim((string)$bankid) : null;
        $acctid = !empty($acctid) ? trim((string)$acctid) : null;
        $intu_bid = !empty($intu_bid) ? trim((string)$intu_bid) : null;

        // If all identifiers are empty, return null
        if (empty($bankid) && empty($acctid) && empty($intu_bid)) {
            return null;
        }

        $table = self::getTableName();
        $conditions = [];

        if (!empty($acctid)) {
            $conditions[] = "IFNULL(acctid,'')=" . db_escape($acctid);
        }
        if (!empty($bankid)) {
            $conditions[] = "IFNULL(bankid,'')=" . db_escape($bankid);
        }
        if (!empty($intu_bid)) {
            $conditions[] = "IFNULL(intu_bid,'')=" . db_escape($intu_bid);
        }

        if (empty($conditions)) {
            return null;
        }

        $whereClause = implode(' AND ', $conditions);
        $sql = "SELECT id, bank_account_id, intu_bid, bankid, acctid, accttype, curdef, updated_ts
                FROM `{$table}`
                WHERE {$whereClause}
                LIMIT 1";

        $res = @db_query($sql, 'Could not find bank account mapping by OFX identifiers');
        if (!is_object($res) || db_num_rows($res) === 0) {
            return null;
        }

        $row = db_fetch($res);
        return is_array($row) ? new BankAccountMapping($row) : null;
    }

    /**
     * Find all mappings for a specific FA bank account
     * 
     * @param int $bankAccountId FA bank account ID
     * @return BankAccountMapping[]
     */
    public static function findByFABankAccountId(int $bankAccountId): array
    {
        if ($bankAccountId <= 0 || !self::tableExists()) {
            return [];
        }

        $table = self::getTableName();
        $sql = "SELECT id, bank_account_id, intu_bid, bankid, acctid, accttype, curdef, updated_ts
                FROM `{$table}`
                WHERE bank_account_id=" . (int)$bankAccountId . "
                ORDER BY updated_ts DESC";

        $res = @db_query($sql, 'Could not fetch bank account mappings');
        if (!is_object($res) || db_num_rows($res) === 0) {
            return [];
        }

        $mappings = [];
        while ($row = db_fetch($res)) {
            if (is_array($row)) {
                $mappings[] = new BankAccountMapping($row);
            }
        }

        return $mappings;
    }

    /**
     * Get all bank account mappings
     * 
     * @param int $limit Maximum number of results
     * @param int $offset Offset for pagination
     * @return BankAccountMapping[]
     */
    public static function getAllMappings(int $limit = 100, int $offset = 0): array
    {
        if (!self::tableExists()) {
            return [];
        }

        $table = self::getTableName();
        $limit = max(1, (int)$limit);
        $offset = max(0, (int)$offset);

        $sql = "SELECT id, bank_account_id, intu_bid, bankid, acctid, accttype, curdef, updated_ts
                FROM `{$table}`
                ORDER BY updated_ts DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $res = @db_query($sql, 'Could not fetch all bank account mappings');
        if (!is_object($res) || db_num_rows($res) === 0) {
            return [];
        }

        $mappings = [];
        while ($row = db_fetch($res)) {
            if (is_array($row)) {
                $mappings[] = new BankAccountMapping($row);
            }
        }

        return $mappings;
    }

    /**
     * Insert or update a bank account mapping
     * 
     * @param BankAccountMapping $mapping The mapping to store
     * @param int $bankAccountId FA bank account ID (optional, can be in mapping)
     * @return int The ID of the mapping (inserted or existing)
     */
    public static function upsert(BankAccountMapping $mapping, ?int $bankAccountId = null): int
    {
        if (!self::tableExists()) {
            return 0;
        }

        // Use provided FA account ID or get from mapping
        $faAccountId = $bankAccountId ?? $mapping->bank_account_id ?? 0;

        // Normalize fields
        $intu_bid = !empty($mapping->intu_bid) ? trim((string)$mapping->intu_bid) : '';
        $bankid = !empty($mapping->bankid) ? trim((string)$mapping->bankid) : '';
        $acctid = !empty($mapping->acctid) ? trim((string)$mapping->acctid) : '';
        $accttype = !empty($mapping->accttype) ? trim((string)$mapping->accttype) : '';
        $curdef = !empty($mapping->curdef) ? trim((string)$mapping->curdef) : '';

        // Avoid inserting meaningless "blank identity" rows
        if (empty($acctid) && empty($bankid) && empty($intu_bid)) {
            return 0;
        }

        $table = self::getTableName();

        // Check if mapping already exists
        $findSql = "SELECT id FROM `{$table}`
                    WHERE IFNULL(acctid,'')=" . db_escape($acctid) . "
                      AND IFNULL(bankid,'')=" . db_escape($bankid) . "
                      AND IFNULL(intu_bid,'')=" . db_escape($intu_bid) . "
                    LIMIT 1";

        $res = @db_query($findSql, 'Could not check existing bank account mapping');
        if (is_object($res) && db_num_rows($res) > 0) {
            $row = db_fetch($res);
            $existingId = is_array($row) && isset($row['id']) ? (int)$row['id'] : 0;

            if ($existingId > 0) {
                // Update existing mapping
                $updateSql = "UPDATE `{$table}`
                              SET bank_account_id=" . (int)$faAccountId . ",
                                  updated_ts=CURRENT_TIMESTAMP,
                                  acctid=" . db_escape($acctid) . ",
                                  bankid=" . db_escape($bankid) . ",
                                  intu_bid=" . db_escape($intu_bid) . ",
                                  accttype=" . db_escape($accttype) . ",
                                  curdef=" . db_escape($curdef) . "
                              WHERE id=" . (int)$existingId;

                @db_query($updateSql, 'Could not update bank account mapping');
                return $existingId;
            }
        }

        // Insert new mapping
        $insertSql = "INSERT INTO `{$table}` (bank_account_id, updated_ts, intu_bid, bankid, acctid, accttype, curdef)
                      VALUES (" . (int)$faAccountId . ", CURRENT_TIMESTAMP, " . db_escape($intu_bid) . ", " . db_escape($bankid) . ", " . db_escape($acctid) . ", " . db_escape($accttype) . ", " . db_escape($curdef) . ")";

        @db_query($insertSql, 'Could not insert bank account mapping');

        // Return the inserted ID (MySQL LAST_INSERT_ID)
        $idRes = @db_query("SELECT LAST_INSERT_ID() as id", "Could not get last insert ID");
        if (is_object($idRes) && db_num_rows($idRes) > 0) {
            $idRow = db_fetch($idRes);
            return is_array($idRow) && isset($idRow['id']) ? (int)$idRow['id'] : 0;
        }

        return 0;
    }

    /**
     * Delete a bank account mapping by ID
     * 
     * @param int $id The mapping ID
     * @return bool True if deleted, false if not found or error
     */
    public static function delete(int $id): bool
    {
        if ($id <= 0 || !self::tableExists()) {
            return false;
        }

        $table = self::getTableName();
        $sql = "DELETE FROM `{$table}` WHERE id=" . (int)$id . " LIMIT 1";

        @db_query($sql, 'Could not delete bank account mapping');
        return true;
    }

    /**
     * Delete all mappings for a specific FA bank account
     * 
     * @param int $bankAccountId FA bank account ID
     * @return int Number of mappings deleted
     */
    public static function deleteByFABankAccountId(int $bankAccountId): int
    {
        if ($bankAccountId <= 0 || !self::tableExists()) {
            return 0;
        }

        $table = self::getTableName();
        $sql = "DELETE FROM `{$table}` WHERE bank_account_id=" . (int)$bankAccountId;

        @db_query($sql, 'Could not delete bank account mappings');
        
        // Return rows affected
        return (int)db_affected_rows();
    }

    /**
     * Count total bank account mappings
     * 
     * @return int
     */
    public static function countAll(): int
    {
        if (!self::tableExists()) {
            return 0;
        }

        $table = self::getTableName();
        $sql = "SELECT COUNT(*) as count FROM `{$table}`";

        $res = @db_query($sql, 'Could not count bank account mappings');
        if (!is_object($res) || db_num_rows($res) === 0) {
            return 0;
        }

        $row = db_fetch($res);
        return is_array($row) && isset($row['count']) ? (int)$row['count'] : 0;
    }

    /**
     * Count mappings for a specific FA bank account
     * 
     * @param int $bankAccountId FA bank account ID
     * @return int
     */
    public static function countByFABankAccountId(int $bankAccountId): int
    {
        if ($bankAccountId <= 0 || !self::tableExists()) {
            return 0;
        }

        $table = self::getTableName();
        $sql = "SELECT COUNT(*) as count FROM `{$table}` WHERE bank_account_id=" . (int)$bankAccountId;

        $res = @db_query($sql, 'Could not count mappings for bank account');
        if (!is_object($res) || db_num_rows($res) === 0) {
            return 0;
        }

        $row = db_fetch($res);
        return is_array($row) && isset($row['count']) ? (int)$row['count'] : 0;
    }
}
