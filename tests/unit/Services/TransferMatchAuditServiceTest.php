<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use KsfBankImport\Services\TransferMatchAuditService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../Services/TransferMatchAuditService.php';

final class TransferMatchAuditServiceTest extends TestCase
{
    public function testRunAuditsLeavesValidUnpostedPairUnflagged(): void
    {
        $transactions = $this->createMock(\bi_transactions_model::class);
        $transferMatches = $this->createMock(\bi_transfer_matches_model::class);

        $transferMatches
            ->expects($this->once())
            ->method('get_confirmed_matches')
            ->with(2000)
            ->willReturn([
                [
                    'debit_transaction_id' => 10,
                    'credit_transaction_id' => 11,
                ],
            ]);

        $transactions
            ->method('get_transaction')
            ->willReturnCallback(static function (int $id): array {
                if ($id === 10) {
                    return [
                        'id' => 10,
                        'transactionDC' => 'D',
                        'status' => 0,
                        'fa_trans_type' => 0,
                        'fa_trans_no' => 0,
                    ];
                }

                if ($id === 11) {
                    return [
                        'id' => 11,
                        'transactionDC' => 'C',
                        'status' => 0,
                        'fa_trans_type' => 0,
                        'fa_trans_no' => 0,
                    ];
                }

                return [];
            });

        $transferMatches
            ->expects($this->once())
            ->method('set_requires_review_by_pair')
            ->with(10, 11, 0);

        $service = new TransferMatchAuditService($transactions, $transferMatches);
        $result = $service->runAudits();

        $this->assertSame(
            [
                'rows_checked' => 1,
                'pair_issues' => 0,
                'je_issues' => 0,
                'rows_flagged' => 0,
            ],
            $result
        );
    }

    public function testRunAuditsFlagsPairIssueForInvalidDebitCreditDirections(): void
    {
        $transactions = $this->createMock(\bi_transactions_model::class);
        $transferMatches = $this->createMock(\bi_transfer_matches_model::class);

        $transferMatches
            ->expects($this->once())
            ->method('get_confirmed_matches')
            ->willReturn([
                [
                    'debit_transaction_id' => 20,
                    'credit_transaction_id' => 21,
                ],
            ]);

        $transactions
            ->method('get_transaction')
            ->willReturnCallback(static function (int $id): array {
                if ($id === 20) {
                    return [
                        'id' => 20,
                        'transactionDC' => 'C',
                        'status' => 0,
                    ];
                }

                if ($id === 21) {
                    return [
                        'id' => 21,
                        'transactionDC' => 'C',
                        'status' => 0,
                    ];
                }

                return [];
            });

        $transferMatches
            ->expects($this->once())
            ->method('set_requires_review_by_pair')
            ->with(20, 21, 1);

        $service = new TransferMatchAuditService($transactions, $transferMatches);
        $result = $service->runAudits();

        $this->assertSame(1, $result['pair_issues']);
        $this->assertSame(0, $result['je_issues']);
        $this->assertSame(1, $result['rows_flagged']);
    }

    public function testRunAuditsFlagsJeIssueWhenPostedPairMissingSharedReference(): void
    {
        $transactions = $this->createMock(\bi_transactions_model::class);
        $transferMatches = $this->createMock(\bi_transfer_matches_model::class);

        $transferMatches
            ->expects($this->once())
            ->method('get_confirmed_matches')
            ->willReturn([
                [
                    'debit_transaction_id' => 30,
                    'credit_transaction_id' => 31,
                ],
            ]);

        $transactions
            ->method('get_transaction')
            ->willReturnCallback(static function (int $id): array {
                if ($id === 30) {
                    return [
                        'id' => 30,
                        'transactionDC' => 'D',
                        'status' => 1,
                        'fa_trans_type' => 0,
                        'fa_trans_no' => 0,
                    ];
                }

                if ($id === 31) {
                    return [
                        'id' => 31,
                        'transactionDC' => 'C',
                        'status' => 1,
                        'fa_trans_type' => 0,
                        'fa_trans_no' => 0,
                    ];
                }

                return [];
            });

        $transferMatches
            ->expects($this->once())
            ->method('set_requires_review_by_pair')
            ->with(30, 31, 1);

        $service = new TransferMatchAuditService($transactions, $transferMatches);
        $result = $service->runAudits();

        $this->assertSame(0, $result['pair_issues']);
        $this->assertSame(1, $result['je_issues']);
        $this->assertSame(1, $result['rows_flagged']);
    }
}
