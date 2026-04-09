<?php
namespace Ksfraser\FaBankImport\Import\Services\Posting\Interfaces;

use Ksfraser\FaBankImport\Import\Services\Posting\DTOs\PostingRequestDTO;
use Ksfraser\FaBankImport\Import\Services\Posting\DTOs\PostingResultDTO;

/**
 * Main orchestration service for posting (copying) approved duplicates to main transaction table.
 * Coordinates the workflow: check eligibility → copy/archive → log audit → handle errors.
 */
interface IPostingOrchestratorService
{
    /**
     * Execute posting for a single transaction based on review decision.
     * Route handling:
     * - APPROVED + eligible → copy to bi_transactions
     * - REJECTED/INVESTIGATE → archive
     * - PENDING/Amount issues → hold for manual review
     * 
     * @param PostingRequestDTO $request The request with all needed data
     * @return PostingResultDTO The result of the posting attempt
     * @throws \Exception if unrecoverable error occurs
     */
    public function executePosting(PostingRequestDTO $request): PostingResultDTO;

    /**
     * Execute batch posting for approved transactions.
     * Processes all APPROVED duplicates up to the limit.
     * 
     * @param int $limit Maximum transactions to process (default 1000)
     * @return array<PostingResultDTO> Results for each transaction processed
     */
    public function executeBatch(int $limit = 1000): array;

    /**
     * Query posting status for a transaction.
     * Retrieves the audit history to determine if/when transaction was posted.
     * 
     * @param int $duplicateId The duplicate transaction ID
     * @return PostingResultDTO The current posting status
     * @throws PostingException if no posting record found
     */
    public function getPostingStatus(int $duplicateId): PostingResultDTO;
}
