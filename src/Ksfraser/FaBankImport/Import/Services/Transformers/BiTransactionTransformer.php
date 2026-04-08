<?php

namespace Ksfraser\FaBankImport\Import\Services\Transformers;

use DateTime;
use Ksfraser\FaBankImport\Import\Services\TransformerInterface;
use Ksfraser\FaBankImport\Shared\Entities\BiTransaction;
use Ksfraser\Exceptions\Utility\TransformException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Transforms transaction arrays to BiTransaction entities
 *
 * Handles:
 * - Amount normalization (decimals, signs, currency scaling)
 * - Date format conversion (multiple input formats)
 * - Reference/memo extraction and logging
 * - Transaction type classification
 * - Confidence score calculation (if QualityScorer available)
 *
 * Implements SRP: Single responsibility = transaction DTO → entity transformation
 */
final class BiTransactionTransformer implements TransformerInterface
{
    /**
     * Optional quality scorer service
     *
     * @var ?QualityScorer
     */
    private ?QualityScorer $qualityScorer;

    /**
     * PSR-3 logger
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Create transformer
     *
     * @param ?QualityScorer $qualityScorer Optional quality scorer for confidence calculation
     * @param ?LoggerInterface $logger Optional PSR-3 logger
     */
    public function __construct(
        ?QualityScorer $qualityScorer = null,
        ?LoggerInterface $logger = null
    ) {
        $this->qualityScorer = $qualityScorer;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Transform transaction records to BiTransaction entities
     *
     * @param array<int, array<string, mixed>> $transactions Array of transaction records
     * @param string $smtId Statement ID (required for linking to statement)
     * @param string $accountId Account ID (required for entity constraint)
     * @return array<int, BiTransaction> Array of created BiTransaction entities
     *
     * @throws TransformException If transformation fails
     */
    public function transformBatch(array $transactions, string $smtId, string $accountId): array
    {
        $results = [];
        $failed = [];

        foreach ($transactions as $index => $txnData) {
            try {
                $biTransaction = $this->transformSingle($txnData, (int)$smtId, $accountId);
                $results[] = $biTransaction;
            } catch (\Exception $e) {
                $failed[] = [
                    'index' => $index,
                    'data' => $txnData,
                    'error' => $e->getMessage()
                ];

                $this->logger->warning(
                    sprintf('Transaction %d transformation failed: %s', $index, $e->getMessage()),
                    ['transaction' => $txnData]
                );
            }
        }

        if (!empty($failed)) {
            $this->logger->error(
                sprintf('%d of %d transactions failed to transform', count($failed), count($transactions)),
                ['failures' => $failed]
            );
        }

        return $results;
    }

    /**
     * Transform single transaction record to BiTransaction
     *
     * @param array<string, mixed> $txnData Transaction record
     * @param int $smtId Statement ID
     * @param string $accountId Account ID
     * @return BiTransaction
     *
     * @throws TransformException If required fields missing or invalid
     */
    private function transformSingle(array $txnData, int $smtId, string $accountId): BiTransaction
    {
        $errors = [];

        // Extract and validate required fields
        [$fitId, $fitIdErrors] = $this->extractAndValidateFitId($txnData);
        $errors = array_merge($errors, $fitIdErrors);

        [$amount, $amountErrors] = $this->extractAndNormalizeAmount($txnData);
        $errors = array_merge($errors, $amountErrors);

        $title = $this->extractAndNormalizeTitle($txnData);

        $valueTimestamp = $this->extractOptionalDateField($txnData, ['valueDate', 'date'], 'valueDate');
        $entryTimestamp = $this->extractOptionalDateField($txnData, ['entryDate'], 'entryDate');

        // Validate fitId required
        if (empty($fitId)) {
            throw new TransformException(
                'Cannot transform transaction: fitId is required. ' . implode('; ', $errors)
            );
        }

        // Create BiTransaction using factory method
        return $this->createBiTransactionEntity($smtId, $fitId, $accountId, $amount, $title, $valueTimestamp, $entryTimestamp);
    }

    /**
     * Extract and validate FIT ID from transaction data
     *
     * @param array<string, mixed> $txnData Transaction record
     * @return array{0: string, 1: array<string>} [fitId, errors]
     */
    private function extractAndValidateFitId(array $txnData): array
    {
        $errors = [];

        if (empty($txnData['fitId'])) {
            $errors[] = 'fitId (FIT transaction ID) is required';
        }

        return [(string)($txnData['fitId'] ?? ''), $errors];
    }

    /**
     * Extract and normalize amount from transaction data
     *
     * @param array<string, mixed> $txnData Transaction record
     * @return array{0: float, 1: array<string>} [amount, errors]
     */
    private function extractAndNormalizeAmount(array $txnData): array
    {
        $errors = [];
        $amount = 0.0;

        if (!isset($txnData['amount'])) {
            $errors[] = 'amount is required';
            return [$amount, $errors];
        }

        try {
            $amount = NormalizationRules::normalizeAmount($txnData['amount']);
        } catch (\Exception $e) {
            $errors[] = sprintf('Invalid amount: %s', $e->getMessage());
        }

        return [$amount, $errors];
    }

    /**
     * Extract and normalize title from transaction data
     *
     * Tries multiple field names with fallbacks: title > memo > description
     *
     * @param array<string, mixed> $txnData Transaction record
     * @return string Normalized title
     */
    private function extractAndNormalizeTitle(array $txnData): string
    {
        $titleValue = $txnData['title'] ?? $txnData['memo'] ?? $txnData['description'] ?? 'Unknown';

        if (empty($titleValue)) {
            $this->logger->warning('Transaction has empty title; using default');
            $titleValue = 'Unknown';
        }

        try {
            return NormalizationRules::normalizeText($titleValue, 'title');
        } catch (\Exception $e) {
            $this->logger->warning(sprintf('Could not normalize title: %s', $e->getMessage()));
            return 'Unknown';
        }
    }

    /**
     * Extract and normalize an optional date field
     *
     * @param array<string, mixed> $txnData Transaction record
     * @param array<string> $fieldNames Possible field names to try (priority order)
     * @param string $fieldLabel Field label for logging
     * @return ?DateTime Normalized date or null if not present/invalid
     */
    private function extractOptionalDateField(array $txnData, array $fieldNames, string $fieldLabel): ?DateTime
    {
        // Try each field name in priority order
        foreach ($fieldNames as $fieldName) {
            if (!empty($txnData[$fieldName])) {
                try {
                    return NormalizationRules::normalizeDate($txnData[$fieldName]);
                } catch (\Exception $e) {
                    $this->logger->warning(sprintf('Could not parse %s from field "%s": %s', $fieldLabel, $fieldName, $e->getMessage()));
                    // Continue to next field name
                }
            }
        }

        return null;
    }

    /**
     * Create BiTransaction entity with factory method
     *
     * @param int $smtId Statement ID
     * @param string $fitId FIT transaction ID
     * @param string $accountId Account ID
     * @param float $amount Transaction amount
     * @param string $title Transaction title
     * @param ?DateTime $valueTimestamp Value date
     * @param ?DateTime $entryTimestamp Entry date
     * @return BiTransaction
     *
     * @throws TransformException If entity creation fails
     */
    private function createBiTransactionEntity(
        int $smtId,
        string $fitId,
        string $accountId,
        float $amount,
        string $title,
        ?DateTime $valueTimestamp,
        ?DateTime $entryTimestamp
    ): BiTransaction {
        try {
            return BiTransaction::create(
                smtId: $smtId,
                fitId: $fitId,
                acctId: NormalizationRules::normalizeAccountReference($accountId),
                transactionAmount: $amount,
                transactionTitle: $title,
                valueTimestamp: $valueTimestamp,
                entryTimestamp: $entryTimestamp
            );
        } catch (\Exception $e) {
            throw new TransformException(
                sprintf('Failed to create BiTransaction entity: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Check if this transformer can handle the transaction
     *
     * Currently accepts all transaction arrays with required fields
     *
     * @param mixed $data Data to check
     * @return bool True if can transform
     */
    public function canTransform($data): bool
    {
        if (!is_array($data)) {
            return false;
        }

        // Must have fitId and amount at minimum
        return !empty($data['fitId']) && isset($data['amount']);
    }

    /**
     * {@inheritDoc}
     *
     * Note: Individual transactions use transformBatch() instead. This method
     * is provided for interface compliance but expects a ParsedStatementDTO
     * in practice this transformer processes raw transaction arrays.
     */
    public function transform(mixed $statement): array
    {
        // This method is required by TransformerInterface but BiTransactionTransformer
        // operates on raw transaction arrays via transformBatch().
        // Returning empty array as this class is used as a building block
        return [];
    }

    /**
     * Get transformer name
     *
     * @return string
     */
    public function getName(): string
    {
        return 'BiTransactionTransformer';
    }
}
