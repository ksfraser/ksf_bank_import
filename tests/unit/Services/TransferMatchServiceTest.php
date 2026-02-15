<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use KsfBankImport\Services\TransferMatchService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../Services/TransferMatchService.php';

final class TransferMatchServiceTest extends TestCase
{
    public function testRunCandidateMatchingBuildsCandidatesAndSummary(): void
    {
        $transactions = $this->createMock(\bi_transactions_model::class);
        $transferMatches = $this->createMock(\bi_transfer_matches_model::class);

        $rows = [
            [
                'id' => 10,
                'status' => 0,
                'transactionAmount' => -100.00,
                'valueTimestamp' => '2025-01-10 10:00:00',
                'transactionDC' => 'D',
                'our_account' => 'BANK-A',
            ],
            [
                'id' => 11,
                'status' => 0,
                'transactionAmount' => 100.00,
                'valueTimestamp' => '2025-01-10 11:00:00',
                'transactionDC' => 'C',
                'our_account' => 'BANK-B',
            ],
        ];

        $transactions
            ->expects($this->once())
            ->method('get_transactions')
            ->willReturn(['XFER' => $rows]);

        $expired = [];
        $transferMatches
            ->expects($this->exactly(2))
            ->method('expire_open_candidates_for_transaction')
            ->willReturnCallback(static function (int $transactionId) use (&$expired): void {
                $expired[] = $transactionId;
            });

        $upserts = [];
        $transferMatches
            ->expects($this->exactly(2))
            ->method('upsert_candidate_pair')
            ->willReturnCallback(static function (
                int $debitTransactionId,
                int $creditTransactionId,
                ?float $confidence,
                ?string $group,
                int $requiresReview
            ) use (&$upserts): void {
                $upserts[] = [
                    'debit' => $debitTransactionId,
                    'credit' => $creditTransactionId,
                    'confidence' => $confidence,
                    'group' => $group,
                    'requires_review' => $requiresReview,
                ];
            });

        $service = new TransferMatchService($transactions, $transferMatches);
        $result = $service->runCandidateMatching('2025-01-01', '2025-01-31', 'ALL', 500);

        $this->assertSame([10, 11], $expired);
        $this->assertCount(2, $upserts);
        $this->assertSame(10, $upserts[0]['debit']);
        $this->assertSame(11, $upserts[0]['credit']);
        $this->assertSame(100.0, $upserts[0]['confidence']);
        $this->assertSame(0, $upserts[0]['requires_review']);

        $this->assertSame(
            [
                'rows_checked' => 2,
                'rows_with_candidates' => 2,
                'rows_requires_review' => 0,
            ],
            $result
        );
    }

    public function testRunCandidateMatchingMarksReviewWhenMultipleCandidatesExist(): void
    {
        $transactions = $this->createMock(\bi_transactions_model::class);
        $transferMatches = $this->createMock(\bi_transfer_matches_model::class);

        $rows = [
            [
                'id' => 1,
                'status' => 0,
                'transactionAmount' => -250.00,
                'valueTimestamp' => '2025-02-01 09:00:00',
                'transactionDC' => 'D',
                'our_account' => 'BANK-A',
            ],
            [
                'id' => 2,
                'status' => 0,
                'transactionAmount' => 250.00,
                'valueTimestamp' => '2025-02-01 09:10:00',
                'transactionDC' => 'C',
                'our_account' => 'BANK-B',
            ],
            [
                'id' => 3,
                'status' => 0,
                'transactionAmount' => 250.00,
                'valueTimestamp' => '2025-02-02 09:10:00',
                'transactionDC' => 'C',
                'our_account' => 'BANK-C',
            ],
        ];

        $transactions
            ->expects($this->once())
            ->method('get_transactions')
            ->willReturn(['XFER' => $rows]);

        $transferMatches
            ->expects($this->exactly(3))
            ->method('expire_open_candidates_for_transaction');

        $upserts = [];
        $transferMatches
            ->expects($this->exactly(4))
            ->method('upsert_candidate_pair')
            ->willReturnCallback(static function (
                int $debitTransactionId,
                int $creditTransactionId,
                ?float $confidence,
                ?string $group,
                int $requiresReview
            ) use (&$upserts): void {
                $upserts[] = [
                    'debit' => $debitTransactionId,
                    'credit' => $creditTransactionId,
                    'requires_review' => $requiresReview,
                ];
            });

        $service = new TransferMatchService($transactions, $transferMatches);
        $result = $service->runCandidateMatching('2025-02-01', '2025-02-05', 'ALL', 500);

        $this->assertSame(1, $result['rows_requires_review']);
        $reviewRows = array_values(array_filter($upserts, static function (array $u): bool {
            return $u['requires_review'] === 1;
        }));
        $this->assertCount(2, $reviewRows);
    }

    public function testConfirmMatchResolvesDebitCreditOrderBeforeConfirming(): void
    {
        $transactions = $this->createMock(\bi_transactions_model::class);
        $transferMatches = $this->createMock(\bi_transfer_matches_model::class);

        $transactions
            ->expects($this->exactly(2))
            ->method('get_transaction')
            ->willReturnCallback(static function (int $id): array {
                if ($id === 200) {
                    return [
                        'id' => 200,
                        'transactionDC' => 'C',
                        'transactionAmount' => 300.00,
                        'status' => 0,
                    ];
                }

                if ($id === 199) {
                    return [
                        'id' => 199,
                        'transactionDC' => 'D',
                        'transactionAmount' => -300.00,
                        'status' => 0,
                    ];
                }

                return [];
            });

        $transferMatches
            ->expects($this->once())
            ->method('confirm_pair')
            ->with(199, 200, 88.5, null);

        $service = new TransferMatchService($transactions, $transferMatches);
        $service->confirmMatch(200, 199, 88.5);
    }

    public function testConfirmMatchThrowsWhenTransactionsNotFound(): void
    {
        $transactions = $this->createMock(\bi_transactions_model::class);
        $transferMatches = $this->createMock(\bi_transfer_matches_model::class);

        $transactions
            ->expects($this->exactly(2))
            ->method('get_transaction')
            ->willReturn([]);

        $service = new TransferMatchService($transactions, $transferMatches);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot confirm transfer: transaction(s) not found.');

        $service->confirmMatch(999, 1000, 100.0);
    }
}
