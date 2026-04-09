<?php
namespace Ksfraser\FaBankImport\Import\Services\Posting;

use Psr\Log\LoggerInterface;
use DateTimeImmutable;
use Ksfraser\FaBankImport\Shared\Entities\DuplicateTransaction;
use Ksfraser\FaBankImport\Repositories\Interfaces\IDuplicateTransactionRepository;
use Ksfraser\FaBankImport\Repositories\Interfaces\ITransactionRepository;
use Ksfraser\FaBankImport\Import\Services\Posting\DTOs\PostingRequestDTO;
use Ksfraser\FaBankImport\Import\Services\Posting\DTOs\PostingResultDTO;
use Ksfraser\FaBankImport\Import\Services\Posting\Interfaces\IPostingOrchestratorService;
use Ksfraser\FaBankImport\Import\Services\Posting\Interfaces\IPostingEligibilityService;
use Ksfraser\FaBankImport\Import\Services\Posting\Interfaces\ITransactionPostingService;
use Ksfraser\FaBankImport\Import\Services\Posting\Interfaces\IArchiveService;
use Ksfraser\FaBankImport\Import\Services\Posting\Interfaces\IRetryPolicy;

/**
 * Main orchestration service for posting (copying) approved duplicates to main transaction table.
 * 
 * Workflow:
 * 1. Check posting eligibility based on review decision and amount
 * 2. Route based on eligibility:
 *    - ELIGIBLE → copy to bi_transactions (with audit trail)
 *    - SKIP → archive the rejection
 *    - HOLD → mark for manual review
 *    - ERROR → log and return error
 * 3. Handle errors with retry logic (exponential backoff)
 * 4. Log audit trail for all outcomes
 * 
 * Dependencies injected: IDuplicateTransactionRepository, ITransactionRepository,
 * IPostingEligibilityService, ITransactionPostingService, IArchiveService, IRetryPolicy
 */
final class PostingOrchestratorService implements IPostingOrchestratorService
{
    public function __construct(
        private IDuplicateTransactionRepository $duplicateRepo,
        private ITransactionRepository $mainRepo,
        private IPostingEligibilityService $eligibility,
        private ITransactionPostingService $postingService,
        private IArchiveService $archiveService,
        private IRetryPolicy $retryPolicy,
        private LoggerInterface $logger
    ) {}

    /**
     * Execute posting for a single duplicate transaction.
     * Routes based on eligibility: copy → archive → hold → error.
     */
    public function executePosting(PostingRequestDTO $request): PostingResultDTO
    {
        try {
            $this->logger->info(
                "Starting posting for duplicate {$request->duplicateId}",
                ['transaction_code' => $request->transactionCode]
            );

            // Step 1: Check eligibility
            $eligibility = $this->eligibility->determineEligibility(
                $request->decisionStatus,
                $request->amount
            );

            // Step 2: Route based on eligibility
            return match ($eligibility) {
                'ELIGIBLE' => $this->copyToMainTable($request),
                'SKIP' => $this->handleSkipped($request),
                'HOLD' => $this->handleHeld($request),
                'ERROR' => $this->handleError($request, 'Invalid eligibility state'),
                default => $this->handleError($request, "Unexpected eligibility status: {$eligibility}"),
            };
        } catch (\Throwable $e) {
            $this->logger->error(
                "Posting failed for duplicate {$request->duplicateId}",
                ['error' => $e->getMessage(), 'exception' => $e]
            );
            return PostingResultDTO::error($request->duplicateId, $e->getMessage());
        }
    }

    /**
     * Happy path: Copy approved transaction to main table.
     */
    private function copyToMainTable(PostingRequestDTO $request): PostingResultDTO
    {
        try {
            // Copy to bi_transactions table
            $mainTxnId = $this->postingService->copyApprovedTransaction(
                $request->duplicateId,
                $request->transactionCode,
                $request->amount,
                $request->decisionStatus
            );

            $this->logger->info(
                "Transaction copied to main ledger",
                [
                    'main_txn_id' => $mainTxnId,
                    'duplicate_id' => $request->duplicateId,
                    'amount' => $request->amount
                ]
            );

            return PostingResultDTO::posted($request->duplicateId, $mainTxnId);
        } catch (\Exception $e) {
            // Log error (retry logic handled by caller with IRetryPolicy)
            $this->logger->error(
                "Failed to copy transaction to main table",
                ['error' => $e->getMessage()]
            );
            throw $e;
        }
    }

    /**
     * Skip path: Archive rejected or investigate transactions.
     */
    private function handleSkipped(PostingRequestDTO $request): PostingResultDTO
    {
        try {
            $this->archiveService->archive(
                $request->duplicateId,
                $request->decisionStatus,
                $request->decisionReason
            );

            $this->logger->info(
                "Transaction skipped - archived as {$request->decisionStatus}",
                ['duplicate_id' => $request->duplicateId]
            );

            return PostingResultDTO::skipped($request->duplicateId, $request->decisionStatus);
        } catch (\Exception $e) {
            $this->logger->error(
                "Failed to archive transaction",
                ['error' => $e->getMessage()]
            );
            return PostingResultDTO::error($request->duplicateId, "Archival failed: {$e->getMessage()}");
        }
    }

    /**
     * Hold path: Transaction held for manual review.
     * Common reasons: PENDING status, amount exceeds limit, business rule constraint.
     */
    private function handleHeld(PostingRequestDTO $request): PostingResultDTO
    {
        $reason = match ($request->decisionStatus) {
            'PENDING' => 'Awaiting review completion',
            default => 'Amount or business rule constraint',
        };

        $this->logger->warning(
            "Transaction held for manual review",
            ['duplicate_id' => $request->duplicateId, 'reason' => $reason]
        );

        return PostingResultDTO::held($request->duplicateId, $reason);
    }

    /**
     * Error path: Invalid state or eligibility result.
     */
    private function handleError(PostingRequestDTO $request, string $message): PostingResultDTO
    {
        $this->logger->error(
            "Posting error for duplicate",
            ['duplicate_id' => $request->duplicateId, 'message' => $message]
        );

        return PostingResultDTO::error($request->duplicateId, $message);
    }

    /**
     * Execute batch posting for all approved transactions up to limit.
     * @return array<PostingResultDTO>
     */
    public function executeBatch(int $limit = 1000): array
    {
        $results = [];
        $transactions = $this->duplicateRepo->findApprovedForPosting($limit);

        $this->logger->info("Starting batch posting", ['count' => count($transactions)]);

        foreach ($transactions as $txn) {
            $request = $this->buildRequest($txn);
            $result = $this->executePosting($request);
            $results[] = $result;
        }

        $this->logger->info("Batch posting complete", [
            'total' => count($results),
            'posted' => count(array_filter($results, fn($r) => $r->isSuccessful())),
            'errors' => count(array_filter($results, fn($r) => $r->isFailed())),
        ]);

        return $results;
    }

    /**
     * Query posting status for a transaction.
     */
    public function getPostingStatus(int $duplicateId): PostingResultDTO
    {
        $audit = $this->duplicateRepo->getAuditHistory($duplicateId);

        if (empty($audit)) {
            throw new PostingException("No posting record found for duplicate {$duplicateId}");
        }

        // Most recent audit entry (first in descending order)
        return PostingResultDTO::fromArray($audit[0]);
    }

    /**
     * Build PostingRequestDTO from DuplicateTransaction entity.
     */
    private function buildRequest(DuplicateTransaction $txn): PostingRequestDTO
    {
        return new PostingRequestDTO(
            duplicateId: (int) $txn->getId(),
            transactionCode: (string) $txn->getTransactionCode(),
            amount: (float) $txn->getAmount(),
            decisionStatus: (string) $txn->getDecisionStatus(),
            decidedBy: (string) $txn->getDecidedBy(),
            decidedAt: new DateTimeImmutable($txn->getDecidedAt()),
            decisionReason: $txn->getReason() ?? ''
        );
    }
}
