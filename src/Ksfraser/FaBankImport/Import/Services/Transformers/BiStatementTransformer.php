<?php

namespace Ksfraser\FaBankImport\Import\Services\Transformers;

use DateTime;
use Ksfraser\FaBankImport\Import\DTOs\ParsedStatementDTO;
use Ksfraser\FaBankImport\Import\Services\TransformerInterface;
use Ksfraser\FaBankImport\Import\Validators\StatementValidator;
use Ksfraser\FaBankImport\Shared\Entities\BiStatement;
use Ksfraser\Exceptions\Utility\TransformException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Transforms ParsedStatementDTO to BiStatement entity
 *
 * Handles:
 * - Mapping parsed statement data to BiStatement fields
 * - Currency validation and normalization
 * - Account reference extraction and validation
 * - Transaction batch transformation (delegates to BiTransactionTransformer)
 * - Statement validation before transformation
 * - Optional enrichment with metadata
 *
 * Implements SRP: Single responsibility = ParsedStatementDTO → BiStatement transformation
 */
final class BiStatementTransformer implements TransformerInterface
{
    /**
     * Validator for pre-transform validation (optional)
     *
     * @var ?StatementValidator
     */
    private ?StatementValidator $validator;

    /**
     * Transaction transformer service (required)
     *
     * @var BiTransactionTransformer
     */
    private BiTransactionTransformer $transactionTransformer;

    /**
     * ID generation strategy (never null - uses default if not provided)
     *
     * @var IdGenerationStrategy
     */
    private IdGenerationStrategy $idGenerator;

    /**
     * Enrichment service (optional, if available from Phase 2.2.2.3)
     *
     * @var ?object
     */
    private ?object $enrichment;

    /**
     * PSR-3 logger
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Create transformer
     *
     * @param BiTransactionTransformer $transactionTransformer Transaction transformer (required)
     * @param ?StatementValidator $validator Optional statement validator
     * @param ?IdGenerationStrategy $idGenerator Optional ID generation strategy (default: DefaultIdGenerationStrategy)
     * @param ?object $enrichment Optional enrichment service (from Phase 2.2.2.3)
     * @param ?LoggerInterface $logger Optional PSR-3 logger
     */
    public function __construct(
        BiTransactionTransformer $transactionTransformer,
        ?StatementValidator $validator = null,
        ?IdGenerationStrategy $idGenerator = null,
        ?object $enrichment = null,
        ?LoggerInterface $logger = null
    ) {
        $this->transactionTransformer = $transactionTransformer;
        $this->validator = $validator;
        $this->idGenerator = $idGenerator ?? new DefaultIdGenerationStrategy();
        $this->enrichment = $enrichment;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Transform ParsedStatementDTO to BiStatement
     *
     * Process:
     * 1. Optional validation: Run StatementValidator if available
     * 2. Extract and normalize statement metadata
     * 3. Transform transaction batch to BiTransaction entities
     * 4. Create BiStatement entity with transactions
     * 5. Optional enrichment: Add metadata (exchange rates, bank info)
     *
     * @param ParsedStatementDTO $statement Parsed statement DTO
     * @return array<int, mixed> Array containing BiStatement (and possibly enrichment data)
     *
     * @throws TransformException If validation fails or creation fails
     */
    public function transform(ParsedStatementDTO $statement): array
    {
        $this->logger->info(
            sprintf(
                'Transforming parsed statement: %s (%d transactions)',
                $statement->statementDate,
                $statement->getTransactionCount()
            )
        );

        // Step 1: Optional validation
        if ($this->validator !== null) {
            $validationResult = $this->validator->validate($statement);

            if (!$validationResult->isValid()) {
                throw new TransformException(
                    sprintf(
                        'Statement validation failed: %s',
                        implode('; ', $validationResult->getErrors())
                    )
                );
            }

            $this->logger->debug('Statement validation passed');
        }

        // Step 2: Extract and normalize metadata
        try {
            $bank = $this->extractBank($statement);
            $account = $this->extractAccount($statement);
            $currency = NormalizationRules::normalizeCurrency($statement->currency);
            $statementId = $this->idGenerator->generateStatementId($statement);
            $acctId = NormalizationRules::normalizeAccountReference($statement->accountReference);

            // Generate identifiers using strategy pattern
            $fitId = $this->idGenerator->generateFitId($statement);
            $bankId = $this->idGenerator->generateBankId($bank);
            $intuBid = $this->idGenerator->generateIntuBid($statement);
        } catch (\Exception $e) {
            throw new TransformException(
                sprintf('Failed to extract statement metadata: %s', $e->getMessage()),
                0,
                $e
            );
        }

        // Step 3: Transform transaction batch
        try {
            $smtId = 0; // Statement ID will be assigned on persistence
            $transactions = $this->transactionTransformer->transformBatch(
                $statement->transactions,
                (string)$smtId,
                $acctId
            );

            $this->logger->debug(sprintf('Transformed %d transactions', count($transactions)));
        } catch (\Exception $e) {
            throw new TransformException(
                sprintf('Failed to transform transactions: %s', $e->getMessage()),
                0,
                $e
            );
        }

        // Step 4: Create BiStatement entity
        try {
            // Build statement data in database format for fromDatabase() factory
            $statementData = [
                'bank' => $bank,
                'account' => $account,
                'statementId' => $statementId,
                'acctid' => $acctId,
                'fitid' => $fitId,
                'bankid' => $bankId,
                'intu_bid' => $intuBid,
                'currency' => $currency,
                'startBalance' => $statement->openingBalance,
                'endBalance' => $statement->closingBalance,
                'smtDate' => $statement->statementDate
            ];

            $biStatement = BiStatement::fromDatabase($statementData, $transactions);

            $this->logger->info(
                sprintf('Created BiStatement entity: %s', $biStatement->getId() ?? 'new')
            );
        } catch (\Exception $e) {
            throw new TransformException(
                sprintf('Failed to create BiStatement entity: %s', $e->getMessage()),
                0,
                $e
            );
        }

        // Step 5: Optional enrichment
        if ($this->enrichment !== null) {
            try {
                $biStatement = $this->enrichment->enrich($biStatement);
                $this->logger->debug('Statement enrichment completed');
            } catch (\Exception $e) {
                // Non-fatal: log warning but don't fail transformation
                $this->logger->warning(
                    sprintf('Enrichment failed: %s', $e->getMessage())
                );
            }
        }

        return [$biStatement];
    }

    /**
     * Check if this transformer can handle the DTO
     *
     * Accepts all ParsedStatementDTO instances
     *
     * @param mixed $statement Statement to check
     * @return bool True if can transform
     */
    public function canTransform($statement): bool
    {
        return $statement instanceof ParsedStatementDTO;
    }

    /**
     * Get transformer name
     *
     * @return string
     */
    public function getName(): string
    {
        return 'BiStatementTransformer';
    }

    /**
     * Extract bank name from parser metadata or defaults
     *
     * @param ParsedStatementDTO $statement
     * @return string Bank name
     */
    private function extractBank(ParsedStatementDTO $statement): string
    {
        // Try to get from metadata first
        if (!empty($statement->metadata['bank'])) {
            return NormalizationRules::normalizeText($statement->metadata['bank'], 'title');
        }

        // Fall back to parser type (will be normalized)
        return match ($statement->parserType) {
            'ofx', 'qfx' => 'OFX Import',
            'qif' => 'QIF Import',
            'csv' => 'CSV Import',
            default => ucfirst($statement->parserType) . ' Import'
        };
    }

    /**
     * Extract account label from parser metadata or reference
     *
     * @param ParsedStatementDTO $statement
     * @return string Account label/description
     */
    private function extractAccount(ParsedStatementDTO $statement): string
    {
        // Try to get from metadata first
        if (!empty($statement->metadata['accountName'])) {
            return NormalizationRules::normalizeText($statement->metadata['accountName'], 'title');
        }

        // Fall back to account reference
        return $statement->accountReference;
    }
}
