<?php
namespace Ksfraser\FaBankImport\Shared\Factories;

use Ksfraser\FaBankImport\Shared\Entities\BankAccountMapping;

/**
 * Factory for creating BankAccountMapping instances
 *
 * Handles extraction of bank account mapping data from legacy models and normalization
 * of OFX identifiers to ensure consistent lookups.
 */
class BankAccountMappingFactory
{
    /**
     * Create a BankAccountMapping from bi_statements_model data
     * 
     * The statement contains the core OFX identifiers that should be mapped.
     * 
     * @param object $statement bi_statements_model instance
     * @param int|null $bankAccountId FA bank account ID (optional)
     * @return BankAccountMapping|null Returns null if no OFX identifiers found
     */
    public static function createFromStatement($statement, ?int $bankAccountId = null): ?BankAccountMapping
    {
        if (!is_object($statement)) {
            return null;
        }

        // Extract OFX identifiers from statement
        $acctid = self::normalizeOFXAccountId($statement->acctid ?? null);
        $bankid = self::normalizeOFXBankId($statement->bankid ?? null);
        $intu_bid = self::normalizeIntuitBID($statement->intu_bid ?? null);

        // If no identifiers, don't create mapping
        if (empty($acctid) && empty($bankid) && empty($intu_bid)) {
            return null;
        }

        $data = [
            'bank_account_id' => $bankAccountId,
            'acctid' => $acctid,
            'bankid' => $bankid,
            'intu_bid' => $intu_bid,
            'accttype' => $statement->accttype ?? null,
            'curdef' => $statement->currency ?? $statement->curdef ?? null,
        ];

        return new BankAccountMapping($data);
    }

    /**
     * Create a BankAccountMapping from bi_transactions_model data
     * 
     * The transaction should have OFX identifiers (usually cascaded from statement).
     * 
     * @param object $transaction bi_transactions_model instance
     * @param int|null $bankAccountId FA bank account ID (optional)
     * @return BankAccountMapping|null Returns null if no OFX identifiers found
     */
    public static function createFromTransaction($transaction, ?int $bankAccountId = null): ?BankAccountMapping
    {
        if (!is_object($transaction)) {
            return null;
        }

        // Extract OFX identifiers from transaction
        $acctid = self::normalizeOFXAccountId($transaction->acctid ?? null);
        $bankid = self::normalizeOFXBankId($transaction->bankid ?? null);
        $intu_bid = self::normalizeIntuitBID($transaction->intu_bid ?? null);

        // If no identifiers, don't create mapping
        if (empty($acctid) && empty($bankid) && empty($intu_bid)) {
            return null;
        }

        $data = [
            'bank_account_id' => $bankAccountId,
            'acctid' => $acctid,
            'bankid' => $bankid,
            'intu_bid' => $intu_bid,
            'accttype' => null, // Transactions don't typically have accttype
            'curdef' => null,   // Transactions don't typically have curdef
        ];

        return new BankAccountMapping($data);
    }

    /**
     * Create a BankAccountMapping from array data (e.g., from import metadata)
     * 
     * @param array $data Array with keys: acctid, bankid, intu_bid, accttype, curdef, bank_account_id
     * @param int|null $bankAccountId FA bank account ID (optional, overrides $data['bank_account_id'])
     * @return BankAccountMapping|null Returns null if no OFX identifiers found
     */
    public static function createFromArray(array $data, ?int $bankAccountId = null): ?BankAccountMapping
    {
        $acctid = self::normalizeOFXAccountId($data['acctid'] ?? null);
        $bankid = self::normalizeOFXBankId($data['bankid'] ?? null);
        $intu_bid = self::normalizeIntuitBID($data['intu_bid'] ?? null);

        // If no identifiers, don't create mapping
        if (empty($acctid) && empty($bankid) && empty($intu_bid)) {
            return null;
        }

        $mappingData = [
            'bank_account_id' => $bankAccountId ?? $data['bank_account_id'] ?? null,
            'acctid' => $acctid,
            'bankid' => $bankid,
            'intu_bid' => $intu_bid,
            'accttype' => $data['accttype'] ?? null,
            'curdef' => $data['curdef'] ?? null,
        ];

        return new BankAccountMapping($mappingData);
    }

    /**
     * Create BankAccountMapping from bi_counterparty_model data
     * 
     * The counterparty model stores bank_id and account_id which map to OFX identifiers.
     * 
     * @param object $counterparty bi_counterparty_model instance
     * @param int|null $bankAccountId FA bank account ID (optional)
     * @return BankAccountMapping|null Returns null if no OFX identifiers found
     */
    public static function createFromCounterparty($counterparty, ?int $bankAccountId = null): ?BankAccountMapping
    {
        if (!is_object($counterparty)) {
            return null;
        }

        // In counterparty, the fields are named differently
        // bank_id is the OFX bank ID, account_id is the OFX account ID
        $bankid = self::normalizeOFXBankId($counterparty->bank_id ?? null);
        $acctid = self::normalizeOFXAccountId($counterparty->account_id ?? null);
        $intu_bid = self::normalizeIntuitBID($counterparty->intu_bid ?? null);

        // If no identifiers, don't create mapping
        if (empty($acctid) && empty($bankid) && empty($intu_bid)) {
            return null;
        }

        $data = [
            'bank_account_id' => $bankAccountId,
            'acctid' => $acctid,
            'bankid' => $bankid,
            'intu_bid' => $intu_bid,
            'accttype' => null,
            'curdef' => null,
        ];

        return new BankAccountMapping($data);
    }

    /**
     * Normalize an OFX account ID
     * 
     * @param mixed $acctid Raw account ID
     * @return string|null Normalized account ID or null if empty
     */
    public static function normalizeOFXAccountId($acctid): ?string
    {
        if (empty($acctid)) {
            return null;
        }

        $normalized = trim((string)$acctid);
        return !empty($normalized) ? $normalized : null;
    }

    /**
     * Normalize an OFX bank ID
     * 
     * @param mixed $bankid Raw bank ID
     * @return string|null Normalized bank ID or null if empty
     */
    public static function normalizeOFXBankId($bankid): ?string
    {
        if (empty($bankid)) {
            return null;
        }

        $normalized = trim((string)$bankid);
        return !empty($normalized) ? $normalized : null;
    }

    /**
     * Normalize an Intuit BID (Business ID)
     * 
     * @param mixed $intu_bid Raw Intuit BID
     * @return string|null Normalized Intuit BID or null if empty
     */
    public static function normalizeIntuitBID($intu_bid): ?string
    {
        if (empty($intu_bid)) {
            return null;
        }

        $normalized = trim((string)$intu_bid);
        return !empty($normalized) ? $normalized : null;
    }

    /**
     * Compare two OFX identifier sets to see if they match
     * 
     * Useful for finding duplicate mappings.
     * 
     * @param string|null $bankid1
     * @param string|null $acctid1
     * @param string|null $intu_bid1
     * @param string|null $bankid2
     * @param string|null $acctid2
     * @param string|null $intu_bid2
     * @return bool True if the sets match
     */
    public static function areIdentifiersEqual(
        ?string $bankid1,
        ?string $acctid1,
        ?string $intu_bid1,
        ?string $bankid2,
        ?string $acctid2,
        ?string $intu_bid2
    ): bool {
        $bankid1 = self::normalizeOFXBankId($bankid1);
        $bankid2 = self::normalizeOFXBankId($bankid2);
        
        $acctid1 = self::normalizeOFXAccountId($acctid1);
        $acctid2 = self::normalizeOFXAccountId($acctid2);
        
        $intu_bid1 = self::normalizeIntuitBID($intu_bid1);
        $intu_bid2 = self::normalizeIntuitBID($intu_bid2);

        return $bankid1 === $bankid2 && $acctid1 === $acctid2 && $intu_bid1 === $intu_bid2;
    }

    /**
     * Generate a display-friendly key for a set of OFX identifiers
     * 
     * Useful for logging and debugging.
     * 
     * @param string|null $bankid
     * @param string|null $acctid
     * @param string|null $intu_bid
     * @return string Display key like "BANKID:ACCTID" or "INTU_BID"
     */
    public static function generateIdentifierKey(?string $bankid, ?string $acctid, ?string $intu_bid): string
    {
        $parts = [];

        if (!empty($bankid)) {
            $parts[] = $bankid;
        }
        if (!empty($acctid)) {
            $parts[] = $acctid;
        }
        if (!empty($intu_bid)) {
            $parts[] = "intu:" . $intu_bid;
        }

        return implode('|', $parts) ?: 'unknown';
    }
}
