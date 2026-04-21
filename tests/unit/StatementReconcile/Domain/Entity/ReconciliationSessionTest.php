<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\StatementReconcile\Domain\Entity;

use Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\ReconciliationSession;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\ReconciliationException;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\MatchedPair;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\ReconciliationSession
 */
class ReconciliationSessionTest extends TestCase
{
    private function makePair(string $lineId = 'L001', int $txId = 10): MatchedPair
    {
        return new MatchedPair($lineId, $txId, 0.90, ['EXACT_AMOUNT_DATE']);
    }

    public function testCreatePendingDefaults(): void
    {
        $session = ReconciliationSession::createPending(1, [], [], []);

        $this->assertNull($session->getId());
        $this->assertSame(ReconciliationSession::STATUS_PENDING, $session->getStatus());
        $this->assertTrue($session->isPending());
        $this->assertFalse($session->isApproved());
    }

    public function testAddPairAndRemoveFromUnmatched(): void
    {
        $session = ReconciliationSession::createPending(
            1,
            [],
            ['L001', 'L002'],
            []
        );

        $session->addPair($this->makePair('L001', 10));

        $this->assertCount(1, $session->getMatchedPairs());
        $this->assertSame(['L002'], $session->getUnmatchedStatementLineIds());
    }

    public function testAddPairReplacesExistingForSameLine(): void
    {
        $session = ReconciliationSession::createPending(1, [$this->makePair('L001', 10)], [], []);

        // Replace with a higher-confidence pair for same line.
        $newPair = new MatchedPair('L001', 20, 1.00, ['EXACT_AMOUNT_DATE', 'TYPE_MATCH']);
        $session->addPair($newPair);

        $pairs = $session->getMatchedPairs();
        $this->assertCount(1, $pairs);
        $this->assertSame(20, $pairs[0]->getBankTransactionId());
    }

    public function testRemovePairMovesLineToUnmatched(): void
    {
        $session = ReconciliationSession::createPending(1, [$this->makePair('L001', 10)], [], []);

        $session->removePair('L001');

        $this->assertCount(0, $session->getMatchedPairs());
        $this->assertContains('L001', $session->getUnmatchedStatementLineIds());
    }

    public function testApproveTransitionsStatus(): void
    {
        $session = ReconciliationSession::createPending(1, [], [], []);
        $session->approve(99);

        $this->assertTrue($session->isApproved());
        $this->assertSame(99, $session->getPersistedByUserId());
        $this->assertNotNull($session->getPersistedAt());
    }

    public function testDoubleApproveThrows(): void
    {
        $session = ReconciliationSession::createPending(1, [], [], []);
        $session->approve(1);

        $this->expectException(ReconciliationException::class);
        $session->approve(1);
    }

    public function testAddPairOnApprovedSessionThrows(): void
    {
        $session = ReconciliationSession::createPending(1, [], [], []);
        $session->approve(1);

        $this->expectException(ReconciliationException::class);
        $session->addPair($this->makePair());
    }

    public function testRemovePairOnApprovedSessionThrows(): void
    {
        $session = ReconciliationSession::createPending(1, [$this->makePair()], [], []);
        $session->approve(1);

        $this->expectException(ReconciliationException::class);
        $session->removePair('L001');
    }

    public function testFromDatabase(): void
    {
        $row = [
            'id'                   => 5,
            'statement_ocr_id'     => 2,
            'status'               => 'approved',
            'persisted_by_user_id' => 42,
            'persisted_at'         => '2026-04-20 12:00:00',
        ];
        $pairs        = [['statement_line_id' => 'L001', 'bank_transaction_id' => 10, 'match_confidence' => 0.9, 'rules_matched' => []]];
        $unmatchedStmt = [];
        $unmatchedBank = [99];

        $session = ReconciliationSession::fromDatabase($row, $pairs, $unmatchedStmt, $unmatchedBank);

        $this->assertSame(5, $session->getId());
        $this->assertTrue($session->isApproved());
        $this->assertSame(42, $session->getPersistedByUserId());
        $this->assertSame([99], $session->getUnmatchedBankTransactionIds());
    }

    public function testToStorageArray(): void
    {
        $session = ReconciliationSession::createPending(
            3,
            [$this->makePair('L001', 10)],
            ['L002'],
            [55]
        );

        $data = $session->toStorageArray();

        $this->assertSame(3, $data['statement_ocr_id']);
        $this->assertSame('pending', $data['status']);
        $this->assertJson($data['matched_pairs_json']);
        $this->assertJson($data['unmatched_statement_line_ids']);
    }

    public function testIsPendingTrue(): void
    {
        $session = ReconciliationSession::createPending(1, [], [], []);

        $this->assertTrue($session->isPending());
        $this->assertFalse($session->isApproved());
    }

    public function testIsApprovedAfterApprove(): void
    {
        $session = ReconciliationSession::createPending(1, [], [], []);
        $session->approve(7);

        $this->assertTrue($session->isApproved());
        $this->assertFalse($session->isPending());
        $this->assertSame(7, $session->getPersistedByUserId());
        $this->assertNotNull($session->getPersistedAt());
    }

    public function testFromDatabaseWithNullPersistedAt(): void
    {
        $row = [
            'id'                   => 10,
            'statement_ocr_id'     => 1,
            'status'               => 'pending',
            'persisted_by_user_id' => null,
            'persisted_at'         => null,
        ];

        $session = ReconciliationSession::fromDatabase($row, [], [], []);

        $this->assertNull($session->getPersistedByUserId());
        $this->assertNull($session->getPersistedAt());
    }
}
