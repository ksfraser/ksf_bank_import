<?php
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Repository;

use Ksfraser\FaBankImport\Contracts\BankAccountDirectoryInterface;

/**
 * The FA bank accounts directory that delegates to the native responder hook.
 *
 * READS ONLY (FR-007): this class has no write path, so a dependency on it
 * cannot create, modify or delete a bank account.
 *
 * The lookup order is ADR-005 §7: ask `ksf_FA_NativeResponder` first; when
 * the module is not active the hook call leaves the payload untouched and
 * the lookup falls back to FA's own native `get_bank_accounts()`, consumed
 * with FA's `db_fetch_assoc` loop. A module that answers with NoData /
 * ErrorResult (or an unusable envelope) also reaches the fallback, but the
 * class itself never fakes a success: an FA with no bank accounts yields []
 * on either path.
 *
 * @package Ksfraser\FaBankImport\Repository
 * @since 1.0.0
 * @BABOK FR-003, FR-007 | BR-ACCT-001 | UAT-ACCT-001
 * @license GPL-3.0
 */
final class FaBankAccountDirectory implements BankAccountDirectoryInterface
{
    /**
     * The responder module interviewed first.
     */
    private const RESPONDER_MODULE = 'ksf_FA_NativeResponder';

    /**
     * Invokes the hook: $invoker($ext, $method, &$data).
     *
     * @var callable|string,mixed,mixed|mixed
     */
    private $hookInvoker;

    /**
     * Reads every FA bank account natively (fallback).
     *
     * @var callable|mixed
     */
    private $listAll;

    /**
     * Builds the hook request DTO, or null when the responder vendor is absent.
     *
     * @var callable|array<string,mixed>|object|null
     */
    private $dtoFactory;

    /**
     * @param callable|null $hookInvoker Hook entry; defaults to hook_invoke().
     * @param callable|null $listAll     Native lister; defaults to get_bank_accounts().
     * @param callable|null $dtoFactory  Hook DTO builder; tests may inject a stub.
     * @BABOK FR-003, FR-007 | BR-ACCT-001 | UAT-ACCT-001
     */
    public function __construct(callable $hookInvoker = null, callable $listAll = null, callable $dtoFactory = null)
    {
        $this->hookInvoker = $hookInvoker !== null
            ? $hookInvoker
            : static function (string $module, string $verb, &$data) {
                return hook_invoke($module, $verb, $data, null);
            };

        $this->listAll = $listAll !== null
            ? $listAll
            : static function () {
                $result = get_bank_accounts(false);
                if ($result === null || $result === false) {
                    return null;
                }
                if (is_array($result)) {
                    return array_values($result);
                }
                $rows = [];
                while (($row = db_fetch_assoc($result)) !== false) {
                    $rows[] = $row;
                }
                return $rows;
            };

        $this->dtoFactory = $dtoFactory !== null
            ? $dtoFactory
            : static function (array $values) {
                if (!class_exists('\ksfraser\FrontAccounting\NativeResponder\Dto\BankAccountDTO')) {
                    return null;
                }
                return new \ksfraser\FrontAccounting\NativeResponder\Dto\BankAccountDTO($values);
            };
    }

    /**
     * The bank accounts the user can choose from.
     *
     * @return array<int, array{id: int, number: string, name: string}> The bank accounts
     * @BABOK FR-003, FR-007 | BR-ACCT-001 | UAT-ACCT-001
     */
    public function listBankAccounts(): array
    {
        $rows = $this->bankAccountRows();
        if ($rows === null) {
            return [];
        }

        $mapped = [];
        foreach ($rows as $row) {
            $mapped[] = [
                'id' => isset($row['id']) ? (int) $row['id'] : 0,
                'number' => isset($row['bank_account_number']) ? (string) $row['bank_account_number'] : '',
                'name' => isset($row['bank_account_name'])
                    ? (string) $row['bank_account_name']
                    : (isset($row['bank_name']) ? (string) $row['bank_name'] : ''),
            ];
        }

        return $mapped;
    }

    /**
     * Whether a bank account number exists (leading zeros normalized).
     *
     * @param string $number Bank account number.
     * @return bool True when the account exists.
     * @BABOK FR-003, FR-007 | BR-ACCT-001 | UAT-ACCT-001
     */
    public function accountNumberExists(string $number): bool
    {
        $rows = $this->bankAccountRows();
        if ($rows === null) {
            return false;
        }

        return self::accountNumberExistsIn($rows, $number);
    }

    /**
     * Whether a number exists among the given FA bank account rows.
     *
     * Match order: exact, then leading-zero stripped, then the all-zeros
     * `0` convention used by the legacy OFX ACCTID helper.
     *
     * @param array<int, array<string, mixed>> $rows   FA bank account rows.
     * @param string                           $number Bank account number.
     * @return bool True when the account exists.
     * @BABOK FR-003, FR-007 | BR-ACCT-001 | UAT-ACCT-001
     */
    public static function accountNumberExistsIn(array $rows, string $number): bool
    {
        $queryNumber = self::normalizeAccountNumber($number);

        foreach ($rows as $row) {
            if (!isset($row['bank_account_number'])) {
                continue;
            }
            if (self::normalizeAccountNumber((string) $row['bank_account_number']) === $queryNumber) {
                return true;
            }
        }

        return false;
    }

    /**
     * The OFX/QFX ACCTID normalization: leading zeros are irrelevant, and an
     * identifier that is zeros all the way down IS the account written `0`.
     *
     * @param string $number The raw identifier.
     * @return string The comparison token.
     * @BABOK FR-003, FR-007 | BR-ACCT-001 | UAT-ACCT-001
     */
    public static function normalizeAccountNumber(string $number): string
    {
        $stripped = ltrim($number, '0');

        return $stripped === '' ? '0' : $stripped;
    }

    /**
     * Calls SEARCH_BANK_ACCOUNT over the loaded responder module (ADR-005 §7).
     *
     * The unwritten rule respected here: if $data was left as the DTO object
     * that came in, no responder ran (the module is inactive) — that is
     * "no reply", never a success (AGENTS_ARCH §11.2).
     *
     * @param array<string, mixed> $dtoValues The filter fields for the DTO.
     * @return array<int, array<string, mixed>>|null The rows, or null when
     *         the hook was not answered with usable data (module silent,
     *         NoData or ErrorResult all fall through).
     * @BABOK FR-003, FR-007 | BR-ACCT-001 | UAT-ACCT-001
     */

    private function searchThroughHook(array $dtoValues): ?array
    {
        $call = $this->dtoFactory;
        $dto = $call($dtoValues);
        if ($dto === null) {
            return null;
        }

        $data = $dto;
        $invoke = $this->hookInvoker;
        $invoke(self::RESPONDER_MODULE, 'SEARCH_BANK_ACCOUNT', $data);

        if (!is_object($data) || $data === $dto) {
            return null;
        }
        if (method_exists($data, 'isError') && $data->isError()) {
            return null;
        }
        if (method_exists($data, 'hasData') && method_exists($data, 'rows')) {
            return $data->hasData() ? $data->rows() : null;
        }

        return null;
    }

    /**
     * The FA bank account rows: hook first, native fallback.
     *
     * @return array<int, array<string, mixed>>|null The rows, or null when
     *         neither the module nor FA could provide them.
     * @BABOK FR-003, FR-007 | BR-ACCT-001 | UAT-ACCT-001
     */

    private function bankAccountRows(): ?array
    {
        $fromHook = $this->searchThroughHook(['show_inactive' => false]);
        if ($fromHook !== null) {
            return $fromHook;
        }

        $read = $this->listAll;
        $fromFa = $read();
        if ($fromFa === null) {
            return null;
        }

        return (array) $fromFa;
    }
}
