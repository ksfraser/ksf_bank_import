<?php

$path_to_root = "../..";

$commonDir = __DIR__ . '/../ksf_modules_common';
$commonInterface = $commonDir . '/class.generic_fa_interface.php';
$commonDefines = $commonDir . '/defines.inc.php';
$faTypesInc = $commonDir . '/../../includes/types.inc';
$faEnv = strtolower((string)getenv('KSF_FA_ENV'));
$useFaMocks = strtolower((string)getenv('KSF_USE_FA_MOCKS'));
$forceMocks = ($useFaMocks === '1' || $useFaMocks === 'true' || $faEnv === 'dev' || $faEnv === 'test');

if (!$forceMocks && is_file($commonInterface) && is_file($commonDefines) && is_file($faTypesInc)) {
    require_once($commonInterface);
    require_once($commonDefines);
}

if (!class_exists('generic_fa_interface_model') || !defined('TB_PREF')) {
    require_once(__DIR__ . '/includes/fa_stubs.php');
}

if (!$forceMocks) {
    $schemaDescriptorFile = __DIR__ . '/src/Ksfraser/FaBankImport/Schema/BiTransferMatchesSchema.php';
    $schemaInstallerFile = __DIR__ . '/src/Ksfraser/FaBankImport/Service/Schema/BiTransferMatchesSchemaInstaller.php';

    if (is_file($schemaDescriptorFile)) {
        require_once($schemaDescriptorFile);
    }
    if (is_file($schemaInstallerFile)) {
        require_once($schemaInstallerFile);
    }
}

/**
 * Transfer match workflow table (candidate/confirmed/rejected/audit).
 *
 * Keeps matching state out of bi_transactions so imported rows remain closer
 * to source-bank data.
 */
class bi_transfer_matches_model extends generic_fa_interface_model
{
    public static function table_name(): string
    {
        return TB_PREF . 'bi_transfer_matches';
    }

    public static function ensure_schema(): void
    {
        if (!class_exists('\\Ksfraser\\FaBankImport\\Service\\Schema\\BiTransferMatchesSchemaInstaller')) {
            return;
        }

        $installer = new \Ksfraser\FaBankImport\Service\Schema\BiTransferMatchesSchemaInstaller(
            'db_query',
            'db_escape',
            'db_num_rows',
            defined('TB_PREF') ? (string)TB_PREF : ''
        );
        $installer->ensureTable();
    }

    public function __construct()
    {
        parent::__construct(null, null, null, null, null);
    }

    public function expire_open_candidates_for_transaction(int $transactionId): void
    {
        $table = self::table_name();
        $sql = "UPDATE {$table}
            SET match_status='expired', requires_review=0
            WHERE (debit_transaction_id=" . db_escape($transactionId) . " OR credit_transaction_id=" . db_escape($transactionId) . ")
              AND match_status='candidate'";
        db_query($sql, 'Could not expire open transfer candidates');
    }

    public function upsert_candidate_pair(int $debitTransactionId, int $creditTransactionId, ?float $confidence = null, ?string $group = null, int $requiresReview = 0, string $source = 'auto'): void
    {
        $table = self::table_name();

        $sql = "INSERT INTO {$table}
            (debit_transaction_id, credit_transaction_id, from_transaction_id, to_transaction_id, match_status, match_confidence, match_group, requires_review, source, suggested_at, confirmed_at, confirmed_by)
            VALUES (
                " . db_escape($debitTransactionId) . ",
                " . db_escape($creditTransactionId) . ",
                " . db_escape($debitTransactionId) . ",
                " . db_escape($creditTransactionId) . ",
                'candidate',
                " . db_escape($confidence) . ",
                " . db_escape($group) . ",
                " . db_escape($requiresReview) . ",
                " . db_escape($source) . ",
                NOW(),
                NULL,
                NULL
            )
            ON DUPLICATE KEY UPDATE
                from_transaction_id=VALUES(from_transaction_id),
                to_transaction_id=VALUES(to_transaction_id),
                match_status='candidate',
                match_confidence=VALUES(match_confidence),
                match_group=VALUES(match_group),
                requires_review=VALUES(requires_review),
                source=VALUES(source),
                suggested_at=NOW(),
                confirmed_at=NULL,
                confirmed_by=NULL";

        db_query($sql, 'Could not upsert transfer candidate pair');
    }

    public function confirm_pair(int $debitTransactionId, int $creditTransactionId, ?float $confidence = 100.0, ?string $confirmedBy = null): void
    {
        $table = self::table_name();

        $sql = "INSERT INTO {$table}
            (debit_transaction_id, credit_transaction_id, from_transaction_id, to_transaction_id, match_status, match_confidence, requires_review, confirmed_at, confirmed_by)
            VALUES (
                " . db_escape($debitTransactionId) . ",
                " . db_escape($creditTransactionId) . ",
                " . db_escape($debitTransactionId) . ",
                " . db_escape($creditTransactionId) . ",
                'confirmed',
                " . db_escape($confidence) . ",
                0,
                NOW(),
                " . db_escape($confirmedBy) . "
            )
            ON DUPLICATE KEY UPDATE
                from_transaction_id=VALUES(from_transaction_id),
                to_transaction_id=VALUES(to_transaction_id),
                match_status='confirmed',
                match_confidence=VALUES(match_confidence),
                requires_review=0,
                confirmed_at=NOW(),
                confirmed_by=VALUES(confirmed_by)";

        db_query($sql, 'Could not confirm transfer pair');
    }

    public function reject_by_transaction(int $transactionId): void
    {
        $table = self::table_name();
        $sql = "UPDATE {$table}
            SET match_status='rejected', requires_review=1
            WHERE debit_transaction_id=" . db_escape($transactionId) . "
               OR credit_transaction_id=" . db_escape($transactionId);
        db_query($sql, 'Could not reject transfer match rows');
    }

    public function clear_by_transaction(int $transactionId): void
    {
        $table = self::table_name();
        $sql = "DELETE FROM {$table}
            WHERE debit_transaction_id=" . db_escape($transactionId) . "
               OR credit_transaction_id=" . db_escape($transactionId);
        db_query($sql, 'Could not clear transfer matches for transaction');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function get_candidates_for_transaction(int $transactionId): array
    {
        $table = self::table_name();
        $sql = "SELECT m.*, p.valueTimestamp AS peer_valueTimestamp, p.transactionAmount AS peer_transactionAmount,
                       p.transactionDC AS peer_transactionDC, p.transactionTitle AS peer_transactionTitle,
                       ps.account AS peer_our_account
                FROM {$table} m
                JOIN " . TB_PREF . "bi_transactions p
                  ON p.id = CASE
                        WHEN m.debit_transaction_id=" . db_escape($transactionId) . " THEN m.credit_transaction_id
                        ELSE m.debit_transaction_id
                    END
                LEFT JOIN " . TB_PREF . "bi_statements ps ON p.smt_id = ps.id
                WHERE (m.debit_transaction_id=" . db_escape($transactionId) . " OR m.credit_transaction_id=" . db_escape($transactionId) . ")
                  AND m.match_status='candidate'
                ORDER BY m.match_confidence DESC, m.id ASC";

        $res = db_query($sql, 'Could not fetch transfer candidates');
        $rows = array();
        while ($row = db_fetch($res)) {
            $peerId = ((int)$row['debit_transaction_id'] === $transactionId)
                ? (int)$row['credit_transaction_id']
                : (int)$row['debit_transaction_id'];

            $rows[] = array(
                'peer_id' => $peerId,
                'score' => isset($row['match_confidence']) ? (float)$row['match_confidence'] : null,
                'valueTimestamp' => $row['peer_valueTimestamp'] ?? null,
                'transactionAmount' => $row['peer_transactionAmount'] ?? null,
                'transactionDC' => $row['peer_transactionDC'] ?? null,
                'transactionTitle' => $row['peer_transactionTitle'] ?? null,
                'our_account' => $row['peer_our_account'] ?? null,
                'match_id' => (int)$row['id'],
                'match_status' => $row['match_status'] ?? 'candidate',
            );
        }

        return $rows;
    }

    public function get_confirmed_peer_for_transaction(int $transactionId): ?int
    {
        $table = self::table_name();
        $sql = "SELECT debit_transaction_id, credit_transaction_id
                FROM {$table}
                WHERE (debit_transaction_id=" . db_escape($transactionId) . " OR credit_transaction_id=" . db_escape($transactionId) . ")
                  AND match_status='confirmed'
                ORDER BY confirmed_at DESC, id DESC
                LIMIT 1";

        $res = db_query($sql, 'Could not fetch confirmed transfer peer');
        $row = db_fetch($res);
        if (!is_array($row) || empty($row)) {
            return null;
        }

        if ((int)$row['debit_transaction_id'] === $transactionId) {
            return (int)$row['credit_transaction_id'];
        }

        return (int)$row['debit_transaction_id'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function get_confirmed_matches(int $limit = 2000): array
    {
        $table = self::table_name();
        $sql = "SELECT * FROM {$table}
                WHERE match_status='confirmed'
                ORDER BY id DESC
                LIMIT " . (int)$limit;

        $res = db_query($sql, 'Could not fetch confirmed transfer matches');
        $rows = array();
        while ($row = db_fetch($res)) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function set_requires_review_by_pair(int $debitTransactionId, int $creditTransactionId, int $requiresReview): void
    {
        $table = self::table_name();
        $sql = "UPDATE {$table}
                SET requires_review=" . db_escape($requiresReview) . "
                WHERE debit_transaction_id=" . db_escape($debitTransactionId) . "
                  AND credit_transaction_id=" . db_escape($creditTransactionId);
        db_query($sql, 'Could not update transfer match review flag');
    }

    public function set_requires_review_by_transaction(int $transactionId, int $requiresReview): void
    {
        $table = self::table_name();
        $sql = "UPDATE {$table}
                SET requires_review=" . db_escape($requiresReview) . "
                WHERE debit_transaction_id=" . db_escape($transactionId) . "
                   OR credit_transaction_id=" . db_escape($transactionId);
        db_query($sql, 'Could not update transfer match review flag by transaction');
    }
}
