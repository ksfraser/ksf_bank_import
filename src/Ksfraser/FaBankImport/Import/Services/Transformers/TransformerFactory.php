<?php

namespace Ksfraser\FaBankImport\Import\Services\Transformers;

use Ksfraser\Exceptions\File\UnsupportedFileTypeException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Factory for creating transformer instances based on parser type
 *
 * Handles:
 * - Routing parser type to correct transformer
 * - Dependency injection for optional services
 * - Registration of new transformer types
 * - Validation that transformers implement interface
 *
 * Implements SRP: Single responsibility = transformer routing and creation
 */
final class TransformerFactory
{
    /**
     * Map of parser types to transformer instances
     *
     * @var array<string, TransformerInterface>
     */
    private array $transformers = [];

    /**
     * PSR-3 logger
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Create factory
     *
     * @param array<string, TransformerInterface> $transformers Initial transformers map
     * @param ?LoggerInterface $logger Optional PSR-3 logger
     */
    public function __construct(
        array $transformers = [],
        ?LoggerInterface $logger = null
    ) {
        $this->transformers = $transformers;
        $this->logger = $logger ?? new NullLogger();

        $this->logger->debug(
            sprintf('TransformerFactory created with %d transformers', count($transformers))
        );
    }

    /**
     * Create transformer for specified parser type
     *
     * @param string $parserType Parser type (csv, ofx, qif, etc.)
     * @return TransformerInterface Transformer instance
     *
     * @throws UnsupportedFileTypeException If type not supported
     */
    public function create(string $parserType): TransformerInterface
    {
        $parserType = strtolower(trim($parserType));

        if (!isset($this->transformers[$parserType])) {
            $supported = implode(', ', array_keys($this->transformers));

            $this->logger->warning(
                sprintf('Unsupported parser type: %s. Supported: %s', $parserType, $supported)
            );

            throw new UnsupportedFileTypeException(
                sprintf(
                    'No transformer available for parser type "%s". Supported types: %s',
                    $parserType,
                    $supported
                )
            );
        }

        $transformer = $this->transformers[$parserType];

        $this->logger->debug(
            sprintf('Created transformer for parser type: %s', $parserType),
            ['transformerName' => $transformer->getName()]
        );

        return $transformer;
    }

    /**
     * Register a transformer for a parser type
     *
     * @param string $parserType Parser type identifier
     * @param TransformerInterface $transformer Transformer instance
     * @return self For fluent interface
     */
    public function register(string $parserType, TransformerInterface $transformer): self
    {
        $parserType = strtolower(trim($parserType));

        $this->logger->debug(
            sprintf('Registering transformer for parser type: %s', $parserType),
            ['transformerName' => $transformer->getName()]
        );

        $this->transformers[$parserType] = $transformer;

        return $this;
    }

    /**
     * Check if transformer is registered for type
     *
     * @param string $parserType
     * @return bool
     */
    public function supports(string $parserType): bool
    {
        return isset($this->transformers[strtolower(trim($parserType))]);
    }

    /**
     * Get list of supported parser types
     *
     * @return array<string> Parser type identifiers
     */
    public function getSupportedTypes(): array
    {
        return array_keys($this->transformers);
    }

    /**
     * Get transformer info for all registered types
     *
     * @return array<array<string, mixed>> Transformer information
     */
    public function getAvailableTransformers(): array
    {
        $result = [];

        foreach ($this->transformers as $parserType => $transformer) {
            $result[] = [
                'parserType' => $parserType,
                'transformerName' => $transformer->getName(),
                'transformerClass' => get_class($transformer)
            ];
        }

        return $result;
    }

    /**
     * Get transformer count
     *
     * @return int Number of registered transformers
     */
    public function getTransformerCount(): int
    {
        return count($this->transformers);
    }

    /**
     * Create default factory with standard transformers
     *
     * Static factory method for quick setup with all standard transformers
     *
     * @param BiTransactionTransformer $transactionTransformer Transaction transformer (required)
     * @param ?StatementValidator $validator Optional validator
     * @param ?EnrichmentService $enrichment Optional enrichment
     * @param ?QualityScorer $qualityScorer Optional quality scorer
     * @param ?LoggerInterface $logger Optional logger
     * @return self Configured factory with all standard transformers
     */
    public static function createDefault(
        BiTransactionTransformer $transactionTransformer,
        ?StatementValidator $validator = null,
        ?EnrichmentService $enrichment = null,
        ?QualityScorer $qualityScorer = null,
        ?LoggerInterface $logger = null
    ): self {
        // Create main statement transformer with optional services
        $statementTransformer = new BiStatementTransformer(
            $transactionTransformer,
            $validator,
            $enrichment,
            $logger
        );

        // Initialize factory with CSV as default
        $factory = new self(
            [
                'csv' => $statementTransformer,
                // OFX and QIF use same transformer for now (same structure)
                'ofx' => $statementTransformer,
                'qfx' => $statementTransformer,
                'qif' => $statementTransformer,
                'xls' => $statementTransformer,
                'xlsx' => $statementTransformer,
            ],
            $logger
        );

        return $factory;
    }
}
