<?php

namespace Ksfraser\FaBankImport\Import\Services;

use Ksfraser\FaBankImport\Import\Exceptions\BankAccountNotFoundException;
use Ksfraser\FaBankImport\Import\Results\OperationResult;

/**
 * Service for resolving and validating bank accounts.
 * 
 * Wraps FA's bank account lookup functions with consistent
 * error handling for both import and process workflows.
 */
class BankAccountResolver
{
    /**
     * Resolve bank account by account number.
     *
     * @param string $accountNumber
     * @return array Bank account data
     * @throws BankAccountNotFoundException
     */
    public function resolveByAccountNumber(string $accountNumber): array
    {
        if (empty($accountNumber)) {
            throw BankAccountNotFoundException::byAccountNumber($accountNumber);
        }

        try {
            // Use our wrapper: fa_get_bank_account_by_number() 
            // Defined in includes/includes.inc, uses fa_bank_accounts model with fallback
            $account = fa_get_bank_account_by_number($accountNumber);
            
            if (empty($account) || !is_array($account)) {
                throw BankAccountNotFoundException::byAccountNumber($accountNumber);
            }
            
            if (isset($account['ACTIVE']) && !$account['ACTIVE']) {
                throw BankAccountNotFoundException::inactive(
                    $account['id'] ?? $accountNumber, 
                    $accountNumber
                );
            }
            
            return $account;
        } catch (BankAccountNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw BankAccountNotFoundException::byAccountNumber($accountNumber);
        }
    }

    /**
     * Resolve bank account by account ID.
     *
     * @param int $accountId
     * @return array Bank account data
     * @throws BankAccountNotFoundException
     */
    public function resolveByAccountId(int $accountId): array
    {
        if ($accountId <= 0) {
            throw BankAccountNotFoundException::byAccountId($accountId);
        }

        try {
            // Use our wrapper: fa_get_bank_account_by_number() via ID lookup
            // The wrapper in bi_bank_accounts_model handles ID-based queries
            // Alternative direct approach (if wrapper doesn't support ID):
            // $query = db_query("SELECT * FROM {$GLOBALS['db_prefix']}bank_account WHERE id = " . (int)$accountId);
            // $account = db_fetch_assoc($query);
            
            // For now, use the wrapper which internally handles both approaches
            $account = get_bank_account($accountId);
            
            if (empty($account) || !is_array($account)) {
                throw BankAccountNotFoundException::byAccountId($accountId);
            }
            
            return $account;
        } catch (BankAccountNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw BankAccountNotFoundException::byAccountId($accountId);
        }
    }

    /**
     * Validate that an account exists and is active.
     *
     * @param array $account
     * @return OperationResult
     */
    public function validate(array $account): OperationResult
    {
        $result = OperationResult::success();

        if (empty($account)) {
            return OperationResult::failure('Bank account data is empty');
        }

        if (empty($account['id']) || empty($account['account_code'])) {
            return OperationResult::failure('Bank account is missing required fields');
        }

        if (isset($account['ACTIVE']) && !$account['ACTIVE']) {
            return OperationResult::failure(
                "Bank account {$account['account_code']} is inactive"
            );
        }

        return $result;
    }

    /**
     * Check if two accounts are the same.
     *
     * @param array|int $account1 Account data array or ID
     * @param array|int $account2 Account data array or ID
     * @return bool
     */
    public function areSame(array|int $account1, array|int $account2): bool
    {
        $id1 = is_array($account1) ? ($account1['id'] ?? null) : $account1;
        $id2 = is_array($account2) ? ($account2['id'] ?? null) : $account2;

        return $id1 === $id2 && $id1 !== null;
    }
}
