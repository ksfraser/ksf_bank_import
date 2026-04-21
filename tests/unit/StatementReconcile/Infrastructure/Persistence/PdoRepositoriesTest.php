<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\StatementReconcile\Infrastructure\Persistence;

use Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\ReconciliationSession;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\StatementOcr;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\ReconciliationException;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\BankTransactionDto;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\MatchedPair;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\RawOcrResult;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\StatementMetadata;
use Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Persistence\PdoReconciliationSessionRepository;
use Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Persistence\PdoStatementOcrRepository;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Integration-style unit tests for PDO repositories using SQLite in-memory.
 *
 * @covers \Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Persistence\PdoStatementOcrRepository
 * @covers \Ksfraser\FaBankImport\StatementReconcile\Infrastructure\Persistence\PdoReconciliationSessionRepository
 */
class PdoRepositoriesTest extends TestCase
{
    // ------------------------------------------------------------------
    // SQLite schema helpers
    // ------------------------------------------------------------------

    private function makeOcrPdo(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec(<<<SQL
            CREATE TABLE bi_statement_ocr (
                id                   INTEGER PRIMARY KEY AUTOINCREMENT,
                account_identifier   TEXT,
                statement_start_date TEXT NOT NULL,
                statement_end_date   TEXT NOT NULL,
                opening_balance      TEXT NOT NULL,
                closing_balance      TEXT NOT NULL,
                due_date             TEXT,
                lines_json           TEXT,
                raw_ocr_json         TEXT,
                model_metadata       TEXT,
                created_at           TEXT DEFAULT CURRENT_TIMESTAMP
            )
        SQL);
        return $pdo;
    }

    private function makeSessionPdo(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec(<<<SQL
            CREATE TABLE bi_reconciliation_session (
                id                              INTEGER PRIMARY KEY AUTOINCREMENT,
                statement_ocr_id                INTEGER NOT NULL,
                matched_pairs_json              TEXT,
                unmatched_statement_line_ids    TEXT,
                unmatched_bank_transaction_ids  TEXT,
                status                          TEXT NOT NULL DEFAULT 'pending',
                persisted_by_user_id            INTEGER,
                persisted_at                    TEXT,
                created_at                      TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at                      TEXT DEFAULT CURRENT_TIMESTAMP
            )
        SQL);
        return $pdo;
    }

    // ------------------------------------------------------------------
    // Fixture helpers
    // ------------------------------------------------------------------

    private function makeOcr(): StatementOcr
    {
        $metadata = StatementMetadata::fromArray([
            'statement_start_date' => '2026-03-01',
            'statement_end_date'   => '2026-03-31',
            'opening_balance'      => '500.00',
            'closing_balance'      => '1200.00',
            'account_identifier'   => '9999',
        ]);
        return StatementOcr::create($metadata, [], new RawOcrResult('{}', 'gemma4'));
    }

    // ==================================================================
    // PdoStatementOcrRepository
    // ==================================================================

    public function testSaveNewOcrReturnsPositiveId(): void
    {
        $pdo  = $this->makeOcrPdo();
        $repo = new PdoStatementOcrRepository($pdo);
        $id   = $repo->save($this->makeOcr());

        $this->assertGreaterThan(0, $id);
    }

    public function testFindByIdReturnsNullForMissingId(): void
    {
        $pdo  = $this->makeOcrPdo();
        $repo = new PdoStatementOcrRepository($pdo);

        $this->assertNull($repo->findById(9999));
    }

    public function testSaveAndFindById(): void
    {
        $pdo  = $this->makeOcrPdo();
        $repo = new PdoStatementOcrRepository($pdo);
        $ocr  = $this->makeOcr();
        $id   = $repo->save($ocr);

        $found = $repo->findById($id);

        $this->assertNotNull($found);
        $this->assertSame('9999', $found->getMetadata()->getAccountIdentifier());
    }

    public function testSaveExistingOcrUpdatesRecord(): void
    {
        $pdo  = $this->makeOcrPdo();
        $repo = new PdoStatementOcrRepository($pdo);
        $ocr  = $this->makeOcr();
        $id   = $repo->save($ocr);

        // Save again with same ocr (it now has an ID set via fromDatabase trick).
        // We simulate an update by fetching, modifying concepts are limited with immutable
        // entity; verify save returns the same ID.
        $found = $repo->findById($id);
        $this->assertNotNull($found);
        $savedAgain = $repo->save($found);
        $this->assertSame($id, $savedAgain);
    }

    public function testFindByAccountIdentifierReturnsMatches(): void
    {
        $pdo  = $this->makeOcrPdo();
        $repo = new PdoStatementOcrRepository($pdo);
        $repo->save($this->makeOcr()); // account_identifier = '9999'

        $results = $repo->findByAccountIdentifier('9999');

        $this->assertCount(1, $results);
        $this->assertSame('9999', $results[0]->getMetadata()->getAccountIdentifier());
    }

    public function testFindByAccountIdentifierReturnsEmptyWhenNone(): void
    {
        $pdo  = $this->makeOcrPdo();
        $repo = new PdoStatementOcrRepository($pdo);

        $this->assertSame([], $repo->findByAccountIdentifier('0000'));
    }

    public function testSaveThrowsOnDbError(): void
    {
        // Create PDO with non-existent table to force PDOException.
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Do NOT create bi_statement_ocr table.

        $repo = new PdoStatementOcrRepository($pdo);

        $this->expectException(
            \Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\StatementOcrException::class
        );
        $repo->save($this->makeOcr());
    }

    // ==================================================================
    // PdoReconciliationSessionRepository
    // ==================================================================

    public function testSessionSaveNewReturnsPositiveId(): void
    {
        $pdo     = $this->makeSessionPdo();
        $repo    = new PdoReconciliationSessionRepository($pdo);
        $session = ReconciliationSession::createPending(1, [], [], []);

        $id = $repo->save($session);
        $this->assertGreaterThan(0, $id);
    }

    public function testSessionFindByIdReturnsNullForMissing(): void
    {
        $pdo  = $this->makeSessionPdo();
        $repo = new PdoReconciliationSessionRepository($pdo);

        $this->assertNull($repo->findById(9999));
    }

    public function testSessionSaveAndFindById(): void
    {
        $pdo     = $this->makeSessionPdo();
        $repo    = new PdoReconciliationSessionRepository($pdo);
        $session = ReconciliationSession::createPending(5, [], ['L001'], [1]);

        $id    = $repo->save($session);
        $found = $repo->findById($id);

        $this->assertNotNull($found);
        $this->assertSame(ReconciliationSession::STATUS_PENDING, $found->getStatus());
    }

    public function testSessionSaveUpdateKeepsSameId(): void
    {
        $pdo     = $this->makeSessionPdo();
        $repo    = new PdoReconciliationSessionRepository($pdo);
        $session = ReconciliationSession::createPending(1, [], [], []);
        $id      = $repo->save($session);

        $found   = $repo->findById($id);
        $this->assertNotNull($found);
        $savedId = $repo->save($found);

        $this->assertSame($id, $savedId);
    }

    public function testSessionFindLatestByOcrId(): void
    {
        $pdo     = $this->makeSessionPdo();
        $repo    = new PdoReconciliationSessionRepository($pdo);
        $session = ReconciliationSession::createPending(42, [], [], []);
        $repo->save($session);

        $found = $repo->findLatestByStatementOcrId(42);

        $this->assertNotNull($found);
        $this->assertSame(ReconciliationSession::STATUS_PENDING, $found->getStatus());
    }

    public function testSessionFindLatestByOcrIdReturnsNullWhenNone(): void
    {
        $pdo  = $this->makeSessionPdo();
        $repo = new PdoReconciliationSessionRepository($pdo);

        $this->assertNull($repo->findLatestByStatementOcrId(9999));
    }

    public function testSessionApproveChangesStatus(): void
    {
        $pdo     = $this->makeSessionPdo();
        $repo    = new PdoReconciliationSessionRepository($pdo);
        $session = ReconciliationSession::createPending(1, [], [], []);
        $id      = $repo->save($session);

        $repo->approve($id, 7);

        $updated = $repo->findById($id);
        $this->assertNotNull($updated);
        $this->assertSame(ReconciliationSession::STATUS_APPROVED, $updated->getStatus());
    }

    public function testSessionApproveThrowsWhenSessionNotFound(): void
    {
        $pdo  = $this->makeSessionPdo();
        $repo = new PdoReconciliationSessionRepository($pdo);

        $this->expectException(ReconciliationException::class);
        $repo->approve(9999, 1);
    }

    public function testSessionSaveThrowsOnDbError(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // No table created.
        $repo    = new PdoReconciliationSessionRepository($pdo);
        $session = ReconciliationSession::createPending(1, [], [], []);

        $this->expectException(ReconciliationException::class);
        $repo->save($session);
    }

    public function testSessionSaveWithMatchedPair(): void
    {
        $pdo     = $this->makeSessionPdo();
        $repo    = new PdoReconciliationSessionRepository($pdo);
        $pair    = new MatchedPair('L001', 1, 0.95, ['EXACT_AMOUNT_DATE'], 41, 100);
        $session = ReconciliationSession::createPending(1, [$pair], [], []);

        $id    = $repo->save($session);
        $found = $repo->findById($id);

        $this->assertNotNull($found);
        $pairs = $found->getMatchedPairs();
        $this->assertCount(1, $pairs);
        $this->assertSame('L001', $pairs[0]->getStatementLineId());
    }
}
