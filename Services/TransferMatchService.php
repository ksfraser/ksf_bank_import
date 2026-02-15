<?php

namespace KsfBankImport\Services;

require_once(__DIR__ . '/../class.bi_transactions.php');
require_once(__DIR__ . '/../class.bi_transfer_matches.php');

/**
 * External transfer matching service.
 *
 * Runs outside line-item rendering (menu action or cron) and stores
 * transfer candidates for explicit confirmation.
 */
class TransferMatchService
{
    /** @var \bi_transactions_model */
    private $transactions;

    /** @var \bi_transfer_matches_model */
    private $transferMatches;

    public function __construct(?\bi_transactions_model $transactions = null, ?\bi_transfer_matches_model $transferMatches = null)
    {
        $this->transactions = $transactions ?: new \bi_transactions_model();
        $this->transferMatches = $transferMatches ?: new \bi_transfer_matches_model();
    }

    /**
     * @return array<string, int>
     */
    public function runCandidateMatching($fromDate, $toDate, $bankAccount = 'ALL', $limit = null): array
    {
        $groups = $this->transactions->get_transactions(0, $fromDate, $toDate, null, null, $limit, $bankAccount);
        $rows = $this->flattenRows($groups);

        $candidateCount = 0;
        $reviewCount = 0;

        foreach ($rows as $row) {
            $candidates = $this->findCandidatesForRow($row, $rows);
            $requiresReview = count($candidates) > 1 ? 1 : 0;
            $transactionId = (int)($row['id'] ?? 0);

            if ($transactionId <= 0) {
                continue;
            }

            $this->transferMatches->expire_open_candidates_for_transaction($transactionId);

            foreach ($candidates as $candidate) {
                $debitCredit = $this->resolveDebitCreditPair($row, $candidate);
                if ($debitCredit === null) {
                    continue;
                }

                $this->transferMatches->upsert_candidate_pair(
                    $debitCredit['debit_transaction_id'],
                    $debitCredit['credit_transaction_id'],
                    isset($candidate['score']) ? (float)$candidate['score'] : null,
                    $this->buildGroupKey($row),
                    $requiresReview
                );
            }

            if (!empty($candidates)) {
                $candidateCount++;
            }
            if ($requiresReview) {
                $reviewCount++;
            }
        }

        return [
            'rows_checked' => count($rows),
            'rows_with_candidates' => $candidateCount,
            'rows_requires_review' => $reviewCount,
        ];
    }

    public function confirmMatch(int $transactionId, int $peerId, ?float $confidence = 100.0): void
    {
        $transaction = $this->transactions->get_transaction($transactionId);
        $peer = $this->transactions->get_transaction($peerId);

        if (!is_array($transaction) || empty($transaction) || !is_array($peer) || empty($peer)) {
            throw new \InvalidArgumentException('Cannot confirm transfer: transaction(s) not found.');
        }

        $debitCredit = $this->resolveDebitCreditPair($transaction, $peer);
        if ($debitCredit === null) {
            throw new \InvalidArgumentException('Cannot confirm transfer: unable to determine debit/credit pair.');
        }

        $this->transferMatches->confirm_pair(
            $debitCredit['debit_transaction_id'],
            $debitCredit['credit_transaction_id'],
            $confidence,
            null
        );
    }

    private function buildGroupKey(array $row): string
    {
        return 'amt:' . abs((float)($row['transactionAmount'] ?? 0))
            . '|date:' . (string)($row['valueTimestamp'] ?? '')
            . '|bank:' . (string)($row['our_account'] ?? '');
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $groups
     * @return array<int, array<string, mixed>>
     */
    private function flattenRows(array $groups): array
    {
        $rows = [];
        foreach ($groups as $groupRows) {
            if (!is_array($groupRows)) {
                continue;
            }
            foreach ($groupRows as $row) {
                if (is_array($row)) {
                    $rows[] = $row;
                }
            }
        }
        return $rows;
    }

    /**
     * @param array<string, mixed> $target
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function findCandidatesForRow(array $target, array $rows): array
    {
        $matches = [];
        $targetId = (int)($target['id'] ?? 0);
        $targetAmount = abs((float)($target['transactionAmount'] ?? 0));
        $targetDate = (string)($target['valueTimestamp'] ?? '');
        $targetDc = strtoupper((string)($target['transactionDC'] ?? ''));
        $targetBank = (string)($target['our_account'] ?? '');

        foreach ($rows as $peer) {
            $peerId = (int)($peer['id'] ?? 0);
            if ($peerId <= 0 || $peerId === $targetId) {
                continue;
            }
            if ((int)($peer['status'] ?? 0) !== 0) {
                continue;
            }

            $peerAmount = abs((float)($peer['transactionAmount'] ?? 0));
            if ($peerAmount !== $targetAmount) {
                continue;
            }

            $peerDc = strtoupper((string)($peer['transactionDC'] ?? ''));
            if ($peerDc === $targetDc) {
                continue;
            }

            $peerBank = (string)($peer['our_account'] ?? '');
            if ($peerBank === $targetBank) {
                continue;
            }

            $peerDate = (string)($peer['valueTimestamp'] ?? '');
            $daysDiff = $this->daysDiff($targetDate, $peerDate);
            if ($daysDiff > 2) {
                continue;
            }

            $score = 60.0;
            if ($daysDiff === 0) {
                $score += 25.0;
            } elseif ($daysDiff === 1) {
                $score += 15.0;
            } else {
                $score += 10.0;
            }

            $score += 15.0; // opposite DC + different bank account

            $peer['peer_id'] = $peerId;
            $peer['score'] = min(100.0, $score);
            $matches[] = $peer;
        }

        usort($matches, static function (array $a, array $b): int {
            return (float)$b['score'] <=> (float)$a['score'];
        });

        return array_slice($matches, 0, 5);
    }

    private function daysDiff(string $dateA, string $dateB): int
    {
        if ($dateA === '' || $dateB === '') {
            return 999;
        }

        try {
            $a = new \DateTimeImmutable($dateA);
            $b = new \DateTimeImmutable($dateB);
            return abs((int)$a->diff($b)->format('%a'));
        } catch (\Throwable $e) {
            return 999;
        }
    }

    /**
     * @param array<string, mixed> $a
     * @param array<string, mixed> $b
     * @return array<string, int>|null
     */
    private function resolveDebitCreditPair(array $a, array $b): ?array
    {
        $aId = (int)($a['id'] ?? 0);
        $bId = (int)($b['id'] ?? 0);
        if ($aId <= 0 || $bId <= 0 || $aId === $bId) {
            return null;
        }

        $aDc = strtoupper((string)($a['transactionDC'] ?? ''));
        $bDc = strtoupper((string)($b['transactionDC'] ?? ''));

        if ($aDc === 'D' && $bDc === 'C') {
            return [
                'debit_transaction_id' => $aId,
                'credit_transaction_id' => $bId,
                'from_transaction_id' => $aId,
                'to_transaction_id' => $bId,
            ];
        }

        if ($aDc === 'C' && $bDc === 'D') {
            return [
                'debit_transaction_id' => $bId,
                'credit_transaction_id' => $aId,
                'from_transaction_id' => $bId,
                'to_transaction_id' => $aId,
            ];
        }

        return null;
    }
}
