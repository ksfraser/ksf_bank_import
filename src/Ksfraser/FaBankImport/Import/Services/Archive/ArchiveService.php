<?php
/**
 * Archive Service for Duplicate Transactions
 *
 * Manages archiving of REJECTED and INVESTIGATE duplicate transactions
 * from the active review queue into archive storage for audit trail and
 * historical analysis.
 *
 * @package Ksfraser\FaBankImport\Import\Services
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Import\Services\Archive;

use Psr\Log\LoggerInterface;
use Ksfraser\FaBankImport\Shared\Entities\DuplicateTransaction;
use Ksfraser\FaBankImport\Import\Exceptions\EntityNotFoundException;
use Ksfraser\FaBankImport\Import\Exceptions\DuplicateReviewException;

/**
 * ArchiveService
 *
 * Responsible for:
 * - Moving REJECTED/INVESTIGATE duplicates from active table to archive
 * - Maintaining audit trail in archive (with reason, timestamp, user)
 * - Supporting queries on archived transactions
 * - Enforcing data integrity during archival
 *
 * Follows Single Responsibility Principle:
 * - Only responsibility: Managing duplicate transaction archival
 * - Delegates review decisions to DuplicateReviewService
 * - Delegates data access to repositories
 */
class ArchiveService
{
    /**
     * Repository for duplicate transaction access
     */
    private $duplicateTransactionRepository;

    /**
     * Repository for archived duplicate transactions
     */
    private $archiveRepository;

    /**
     * PSR-3 Logger for audit trail
     */
    private $logger;

    /**
     * Constructor
     *
     * @param $duplicateTransactionRepository - Repository for active duplicates
     * @param $archiveRepository - Repository for archive storage
     * @param LoggerInterface $logger - Logger for audit trail
     */
    public function __construct(
        $duplicateTransactionRepository,
        $archiveRepository,
        LoggerInterface $logger
    ) {
        $this->duplicateTransactionRepository = $duplicateTransactionRepository;
        $this->archiveRepository = $archiveRepository;
        $this->logger = $logger;
    }

    /**
     * Archive a REJECTED duplicate transaction
     *
     * Moves transaction from active queue to archive with reason.
     * Only REJECTED transactions can be archived.
     *
     * @param int $duplicateId - ID of duplicate to archive
     * @param string $reason - Reason for rejection (max 500 chars)
     * @param string $archivedBy - User ID of archiving user
     *
     * @throws EntityNotFoundException - If duplicate not found
     * @throws DuplicateReviewException - If transaction not in REJECTED state
     *
     * @return void
     */
    public function archiveRejected(int $duplicateId, string $reason, string $archivedBy): void
    {
        $duplicate = $this->duplicateTransactionRepository->findById($duplicateId);

        if ($duplicate === null) {
            $this->logger->error(
                'Unable to archive duplicate: not found',
                ['duplicate_id' => $duplicateId, 'reason' => $reason]
            );
            throw EntityNotFoundException::forDuplicate($duplicateId);
        }

        if ($duplicate->decisionStatus !== 'REJECTED') {
            $this->logger->error(
                'Unable to archive duplicate: not in REJECTED state',
                ['duplicate_id' => $duplicateId, 'status' => $duplicate->decisionStatus]
            );
            throw new DuplicateReviewException(
                "Cannot archive duplicate {$duplicateId}: status is {$duplicate->decisionStatus}, expected REJECTED"
            );
        }

        try {
            $this->archiveRepository->archive(
                $duplicateId,
                'REJECTED',
                $reason,
                $archivedBy
            );

            $this->logger->info(
                'Duplicate transaction archived (REJECTED)',
                [
                    'duplicate_id' => $duplicateId,
                    'transaction_code' => $duplicate->transactionCode,
                    'archived_by' => $archivedBy,
                    'reason' => $reason
                ]
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Failed to archive rejected duplicate',
                [
                    'duplicate_id' => $duplicateId,
                    'error' => $e->getMessage()
                ]
            );
            throw new DuplicateReviewException(
                "Failed to archive duplicate {$duplicateId}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Archive an INVESTIGATE duplicate transaction
     *
     * Moves transaction from active queue to investigation archive.
     * Only INVESTIGATE transactions can use this method.
     * Investigation archives are kept active for later review.
     *
     * @param int $duplicateId - ID of duplicate to archive
     * @param string $investigationNotes - Notes about investigation (max 1000 chars)
     * @param string $archivedBy - User ID who initiated investigation hold
     *
     * @throws EntityNotFoundException - If duplicate not found
     * @throws DuplicateReviewException - If not in INVESTIGATE state
     *
     * @return void
     */
    public function archiveForInvestigation(int $duplicateId, string $investigationNotes, string $archivedBy): void
    {
        $duplicate = $this->duplicateTransactionRepository->findById($duplicateId);

        if ($duplicate === null) {
            $this->logger->error(
                'Unable to archive duplicate for investigation: not found',
                ['duplicate_id' => $duplicateId]
            );
            throw EntityNotFoundException::forDuplicate($duplicateId);
        }

        if ($duplicate->decisionStatus !== 'INVESTIGATE') {
            $this->logger->error(
                'Unable to archive for investigation: not in INVESTIGATE state',
                ['duplicate_id' => $duplicateId, 'status' => $duplicate->decisionStatus]
            );
            throw new DuplicateReviewException(
                "Cannot archive for investigation: duplicate {$duplicateId} status is {$duplicate->decisionStatus}, expected INVESTIGATE"
            );
        }

        try {
            $this->archiveRepository->archive(
                $duplicateId,
                'INVESTIGATE',
                $investigationNotes,
                $archivedBy
            );

            $this->logger->info(
                'Duplicate transaction archived for investigation',
                [
                    'duplicate_id' => $duplicateId,
                    'transaction_code' => $duplicate->transactionCode,
                    'archived_by' => $archivedBy,
                    'investigation_notes' => $investigationNotes
                ]
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Failed to archive transaction for investigation',
                [
                    'duplicate_id' => $duplicateId,
                    'error' => $e->getMessage()
                ]
            );
            throw new DuplicateReviewException(
                "Failed to archive duplicate {$duplicateId} for investigation: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Count archived transactions by status
     *
     * Returns counts of archived rejected and investigated transactions.
     * Useful for metrics and reporting.
     *
     * @return array - Associative array with 'rejected' and 'investigated' counts
     */
    public function getArchiveStats(): array
    {
        return [
            'rejected' => $this->archiveRepository->countByStatus('REJECTED'),
            'investigated' => $this->archiveRepository->countByStatus('INVESTIGATE')
        ];
    }

    /**
     * Query archived transactions
     *
     * Returns paginated list of archived transactions matching criteria.
     *
     * @param array $filters - Filter criteria (status, date_from, date_to, etc)
     * @param int $page - Page number for pagination
     * @param int $perPage - Results per page
     *
     * @return array - Results with ['items', 'total', 'page', 'per_page']
     */
    public function queryArchived(array $filters, int $page = 1, int $perPage = 25): array
    {
        return $this->archiveRepository->findArchived($filters, $page, $perPage);
    }

    /**
     * Get single archived transaction details
     *
     * @param int $archiveId - ID of archived record
     *
     * @throws EntityNotFoundException - If archived record not found
     *
     * @return array - Archived transaction details
     */
    public function getArchiveDetails(int $archiveId): array
    {
        $record = $this->archiveRepository->findById($archiveId);

        if ($record === null) {
            $this->logger->warning(
                'Archive record not found',
                ['archive_id' => $archiveId]
            );
            throw EntityNotFoundException::forArchive($archiveId);
        }

        return $record;
    }

    /**
     * Bulk archive REJECTED transactions
     *
     * Archives multiple rejected transactions in a single operation.
     * Useful for batch processing.
     *
     * @param array $duplicateIds - Array of duplicate transaction IDs
     * @param string $reason - Reason for bulk rejection
     * @param string $archivedBy - User ID performing archival
     *
     * @return array - Result with ['archived_count', 'failed_ids', 'errors']
     */
    public function bulkArchiveRejected(array $duplicateIds, string $reason, string $archivedBy): array
    {
        $result = [
            'archived_count' => 0,
            'failed_ids' => [],
            'errors' => []
        ];

        foreach ($duplicateIds as $duplicateId) {
            try {
                $this->archiveRejected($duplicateId, $reason, $archivedBy);
                $result['archived_count']++;
            } catch (\Exception $e) {
                $result['failed_ids'][] = $duplicateId;
                $result['errors'][$duplicateId] = $e->getMessage();

                $this->logger->warning(
                    'Failed to archive duplicate in bulk operation',
                    [
                        'duplicate_id' => $duplicateId,
                        'error' => $e->getMessage()
                    ]
                );
            }
        }

        $this->logger->info(
            'Bulk archive operation completed',
            [
                'total_requested' => count($duplicateIds),
                'archived_count' => $result['archived_count'],
                'failed_count' => count($result['failed_ids'])
            ]
        );

        return $result;
    }
}
