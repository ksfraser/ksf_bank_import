<?php

namespace Ksfraser\FaBankImport\Commands;

use Ksfraser\FaBankImport\Services\BiTransactionService;
use Ksfraser\FaBankImport\Models\BiTransaction;
use Ksfraser\FaBankImport\DTOs\BiTransactionDTO;

/**
 * BiTransactionCommandHandler
 * 
 * Processes commands and form submissions for BiTransaction operations.
 * Returns standardized response format for API/web framework integration.
 * Coordinates with service layer for business logic execution.
 * 
 * @package Ksfraser\FaBankImport\Commands
 */
final class BiTransactionCommandHandler
{
    // Error codes
    private const ERROR_INVALID_COMMAND = 'INVALID_COMMAND';
    private const ERROR_MISSING_ID = 'MISSING_ID';
    private const ERROR_MISSING_ACTION = 'MISSING_ACTION';
    private const ERROR_NOT_FOUND = 'NOT_FOUND';
    private const ERROR_UNKNOWN_ACTION = 'UNKNOWN_ACTION';
    private const ERROR_INVALID_DATA = 'INVALID_DATA';
    private const ERROR_OPERATION_FAILED = 'OPERATION_FAILED';

    public function __construct(
        private BiTransactionService $service
    ) {
    }

    /**
     * Handle single command
     */
    public function handle(array $command): array
    {
        try {
            // Validate command structure
            $validation = $this->validateCommand($command);
            if (!$validation['valid']) {
                return $this->errorResponse($validation['errorCode'], $validation['message']);
            }

            $action = $command['action'];
            $id = $command['id'] ?? null;

            // Route to action handler
            return match ($action) {
                'update' => $this->handleUpdate($id, $command['data'] ?? []),
                'toggleDebitCredit' => $this->handleToggleDebitCredit($id),
                'markMatched' => $this->handleMarkMatched($id, $command['matchinfo'] ?? null),
                'markCreated' => $this->handleMarkCreated($id),
                'linkToFA' => $this->handleLinkToFA($id, $command['faTransNo'] ?? null, $command['faTransType'] ?? null),
                'setPartner' => $this->handleSetPartner($id, $command['partnerId'] ?? null, $command['partnerOption'] ?? null),
                'delete' => $this->handleDelete($id),
                'bulkMarkMatched' => $this->handleBulkMarkMatched($command['ids'] ?? []),
                'bulkDelete' => $this->handleBulkDelete($command['ids'] ?? []),
                default => $this->errorResponse(self::ERROR_UNKNOWN_ACTION, "Unknown action: {$action}"),
            };
        } catch (\Exception $e) {
            return $this->errorResponse(self::ERROR_OPERATION_FAILED, $e->getMessage());
        }
    }

    /**
     * Handle batch of commands
     */
    public function handleBatch(array $commands): array
    {
        $results = [];

        foreach ($commands as $command) {
            $results[] = $this->handle($command);
        }

        return $results;
    }

    /**
     * Handle batch with summary
     */
    public function handleBatchWithSummary(array $commands): array
    {
        $results = $this->handleBatch($commands);
        
        $successful = 0;
        $failed = 0;

        foreach ($results as $result) {
            if ($result['success']) {
                $successful++;
            } else {
                $failed++;
            }
        }

        return [
            'results' => $results,
            'summary' => [
                'successful' => $successful,
                'failed' => $failed,
                'total' => count($results),
            ],
        ];
    }

    /**
     * Validate command structure
     */
    private function validateCommand(array $command): array
    {
        if (empty($command)) {
            return ['valid' => false, 'errorCode' => self::ERROR_INVALID_COMMAND, 'message' => 'Command cannot be empty'];
        }

        if (!isset($command['action'])) {
            return ['valid' => false, 'errorCode' => self::ERROR_MISSING_ACTION, 'message' => 'Missing required field: action'];
        }

        // Most actions require ID
        $actionsRequiringId = ['update', 'toggleDebitCredit', 'markMatched', 'markCreated', 'linkToFA', 'setPartner', 'delete'];
        if (in_array($command['action'], $actionsRequiringId) && !isset($command['id'])) {
            return ['valid' => false, 'errorCode' => self::ERROR_MISSING_ID, 'message' => "Action '{$command['action']}' requires ID"];
        }

        return ['valid' => true];
    }

    /**
     * Handle update command
     */
    private function handleUpdate(int $id, array $data): array
    {
        $transaction = $this->service->getTransaction($id);
        
        // Apply updates from data array
        // For now, this is a placeholder - extend with actual update logic as needed
        // Example: if (isset($data['transactionDC'])) { $transaction = $transaction->withDC($data['transactionDC']); }
        
        $this->service->saveTransaction($transaction);

        return $this->successResponse('Transaction updated', $this->entityToArray($transaction));
    }

    /**
     * Handle toggle debit credit command
     */
    private function handleToggleDebitCredit(int $id): array
    {
        $original = $this->service->getTransaction($id);
        $toggled = $this->service->toggleDebitCredit($id);

        return $this->successResponse(
            'Debit/credit toggled',
            $this->entityToArray($toggled),
            [
                'previousDC' => $original->getTransactionDC(),
                'newDC' => $toggled->getTransactionDC(),
            ]
        );
    }

    /**
     * Handle mark matched command
     */
    private function handleMarkMatched(int $id, ?string $matchinfo = null): array
    {
        $transaction = $this->service->markAsMatched($id, matchinfo: $matchinfo);

        return $this->successResponse('Transaction marked as matched', $this->entityToArray($transaction));
    }

    /**
     * Handle mark created command
     */
    private function handleMarkCreated(int $id): array
    {
        $transaction = $this->service->markAsCreated($id);

        return $this->successResponse('Transaction marked as created', $this->entityToArray($transaction));
    }

    /**
     * Handle link to FA command
     */
    private function handleLinkToFA(int $id, ?int $faTransNo = null, ?int $faTransType = null): array
    {
        if ($faTransNo === null || $faTransType === null) {
            return $this->errorResponse(self::ERROR_INVALID_DATA, 'Missing required fields: faTransNo, faTransType');
        }

        $transaction = $this->service->linkToFATransaction($id, $faTransNo, $faTransType);

        return $this->successResponse('Transaction linked to FA', $this->entityToArray($transaction));
    }

    /**
     * Handle set partner command
     */
    private function handleSetPartner(int $id, ?string $partnerId = null, ?string $partnerOption = null): array
    {
        if ($partnerId === null || $partnerOption === null) {
            return $this->errorResponse(self::ERROR_INVALID_DATA, 'Missing required fields: partnerId, partnerOption');
        }

        $transaction = $this->service->setPartnerInfo($id, $partnerId, $partnerOption);

        return $this->successResponse('Partner info set', $this->entityToArray($transaction));
    }

    /**
     * Handle delete command
     */
    private function handleDelete(int $id): array
    {
        $result = $this->service->deleteTransaction($id);

        if (!$result) {
            return $this->errorResponse(self::ERROR_NOT_FOUND, "Transaction {$id} not found");
        }

        return $this->successResponse('Transaction deleted', [], ['deletedId' => $id]);
    }

    /**
     * Handle bulk mark matched command
     */
    private function handleBulkMarkMatched(array $ids): array
    {
        if (empty($ids)) {
            return $this->errorResponse(self::ERROR_INVALID_DATA, 'No IDs provided');
        }

        $results = $this->service->bulkMarkAsMatched($ids);
        $successful = array_sum($results);

        return $this->successResponse(
            "Marked {$successful} transactions as matched",
            [],
            [
                'processed' => count($ids),
                'successful' => $successful,
                'failed' => count($ids) - $successful,
            ]
        );
    }

    /**
     * Handle bulk delete command
     */
    private function handleBulkDelete(array $ids): array
    {
        if (empty($ids)) {
            return $this->errorResponse(self::ERROR_INVALID_DATA, 'No IDs provided');
        }

        $count = $this->service->bulkDelete($ids);

        return $this->successResponse(
            "Deleted {$count} transactions",
            [],
            [
                'processed' => count($ids),
                'successful' => $count,
                'failed' => count($ids) - $count,
            ]
        );
    }

    /**
     * Build success response
     */
    private function successResponse(string $message, array $data = [], array $extras = []): array
    {
        return array_merge([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $extras);
    }

    /**
     * Build error response
     */
    private function errorResponse(string $errorCode, string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'error' => $message,
            'errorCode' => $errorCode,
        ];
    }

    /**
     * Convert entity to array for response
     */
    private function entityToArray(BiTransaction $transaction): array
    {
        return $transaction->toArray();
    }
}
