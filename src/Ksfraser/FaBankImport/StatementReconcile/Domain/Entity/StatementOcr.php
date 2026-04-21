<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Domain\Entity;

use Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\StatementOcrException;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\RawOcrResult;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\StatementLine;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\StatementMetadata;

/**
 * Aggregate root representing one parsed CC statement PDF.
 *
 * Holds:
 * - Statement-level metadata (balances, dates, account id).
 * - All extracted line items.
 * - The raw OCR result for audit / re-processing.
 *
 * Identity: integer $id (null when not yet persisted).
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Domain\Entity
 * @author  Kevin Fraser
 */
final class StatementOcr
{
    /** @var int|null Null until persisted. */
    private $id;

    /** @var StatementMetadata */
    private $metadata;

    /** @var StatementLine[] */
    private $lines;

    /** @var RawOcrResult */
    private $rawOcrResult;

    /** @var \DateTimeImmutable */
    private $createdAt;

    /**
     * Private constructor – use factory methods.
     *
     * @param int|null          $id
     * @param StatementMetadata $metadata
     * @param StatementLine[]   $lines
     * @param RawOcrResult      $rawOcrResult
     * @param \DateTimeImmutable $createdAt
     */
    private function __construct(
        ?int $id,
        StatementMetadata $metadata,
        array $lines,
        RawOcrResult $rawOcrResult,
        \DateTimeImmutable $createdAt
    ) {
        $this->id           = $id;
        $this->metadata     = $metadata;
        $this->lines        = $lines;
        $this->rawOcrResult = $rawOcrResult;
        $this->createdAt    = $createdAt;
    }

    /**
     * Create a new (unpersisted) StatementOcr from parsed model output.
     *
     * @param StatementMetadata $metadata
     * @param StatementLine[]   $lines
     * @param RawOcrResult      $rawOcrResult
     * @return self
     */
    public static function create(
        StatementMetadata $metadata,
        array $lines,
        RawOcrResult $rawOcrResult
    ): self {
        self::assertValidLines($lines);

        return new self(null, $metadata, $lines, $rawOcrResult, new \DateTimeImmutable());
    }

    /**
     * Reconstitute a StatementOcr from a database row + decoded sub-arrays.
     *
     * Expected $row keys: id, created_at (YYYY-MM-DD HH:MM:SS)
     * Expected $metadataArray: same format as StatementMetadata::fromArray()
     * Expected $linesArray: array of arrays, each StatementLine::fromArray() compatible
     * Expected $rawOcrArray: keys raw_json, model_name, model_version
     *
     * @param array $row
     * @param array $metadataArray
     * @param array $linesArray
     * @param array $rawOcrArray
     * @return self
     */
    public static function fromDatabase(
        array $row,
        array $metadataArray,
        array $linesArray,
        array $rawOcrArray
    ): self {
        $id = isset($row['id']) ? (int) $row['id'] : null;

        $createdAt = isset($row['created_at'])
            ? \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $row['created_at'])
            : new \DateTimeImmutable();
        if ($createdAt === false) {
            $createdAt = new \DateTimeImmutable();
        }

        $metadata = StatementMetadata::fromArray($metadataArray);

        $lines = [];
        foreach ($linesArray as $lineData) {
            $lines[] = StatementLine::fromArray($lineData);
        }

        $rawOcrResult = new RawOcrResult(
            (string) ($rawOcrArray['raw_json'] ?? '{}'),
            (string) ($rawOcrArray['model_name'] ?? 'unknown'),
            isset($rawOcrArray['model_version']) ? (string) $rawOcrArray['model_version'] : null
        );

        return new self($id, $metadata, $lines, $rawOcrResult, $createdAt);
    }

    // -------------------------------------------------------------------------
    // Queries
    // -------------------------------------------------------------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMetadata(): StatementMetadata
    {
        return $this->metadata;
    }

    /**
     * @return StatementLine[]
     */
    public function getLines(): array
    {
        return $this->lines;
    }

    public function getLineCount(): int
    {
        return count($this->lines);
    }

    public function getRawOcrResult(): RawOcrResult
    {
        return $this->rawOcrResult;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Return lines filtered by type.
     *
     * @param string $type 'credit' | 'debit'
     * @return StatementLine[]
     */
    public function getLinesByType(string $type): array
    {
        return array_values(
            array_filter($this->lines, static function (StatementLine $l) use ($type): bool {
                return $l->getType() === $type;
            })
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * @param StatementLine[] $lines
     */
    private static function assertValidLines(array $lines): void
    {
        foreach ($lines as $line) {
            if (!($line instanceof StatementLine)) {
                throw StatementOcrException::forReason(
                    'lines array must contain only StatementLine instances'
                );
            }
        }
    }

    /**
     * Serialise to a flat array suitable for PDO persistence.
     * Monetary/date fields are already strings; lines and rawOcr are JSON-encoded.
     *
     * @return array
     */
    public function toStorageArray(): array
    {
        $meta = $this->metadata->toArray();

        $linesData = array_map(static function (StatementLine $l): array {
            return $l->toArray();
        }, $this->lines);

        $modelMeta = [
            'model_name'    => $this->rawOcrResult->getModelName(),
            'model_version' => $this->rawOcrResult->getModelVersion(),
        ];

        return [
            'account_identifier'   => $meta['account_identifier'],
            'statement_start_date' => $meta['statement_start_date'],
            'statement_end_date'   => $meta['statement_end_date'],
            'opening_balance'      => $meta['opening_balance'],
            'closing_balance'      => $meta['closing_balance'],
            'due_date'             => $meta['due_date'],
            'lines_json'           => json_encode($linesData),
            'raw_ocr_json'         => $this->rawOcrResult->getRawJson(),
            'model_metadata'       => json_encode($modelMeta),
        ];
    }
}
