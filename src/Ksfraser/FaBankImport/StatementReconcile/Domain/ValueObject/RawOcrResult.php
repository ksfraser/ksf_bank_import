<?php

declare(strict_types=1);
// @BABOK Referenced in RTM for domain layer (BR-001 through BR-012, ARCH-001 through ARCH-010)
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject;

use InvalidArgumentException;

/**
 * @BABOK CLASS ROLLUP: RawOcrResult — 5 methods; FR-007 (5); BR-004 (5)
 * @BABOK RTM: FR-007 / BR-004 / ARCH-010; file: StatementReconcile/Domain/ValueObject/RawOcrResult.php; coverage: docs/TestCases/ (file rollup)
 * Wraps the raw response from the Ollama/OCR model.
 *
 * Immutable value object. Stores:
 * - The raw JSON string returned by the model (for audit/replay).
 * - Model name and version metadata.
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject
 * @author  Kevin Fraser
 */
final class RawOcrResult
{
    /** @var string Raw JSON string from model response. */
    private $rawJson;

    /** @var string Model name used e.g. "gemma4". */
    private $modelName;

    /** @var string|null Optional model version string. */
    private $modelVersion;

    /**
 * @BABOK FR-007 / BR-004 / ARCH-010 — method: __construct
     * @param string      $rawJson     Must be a valid JSON string.
     * @param string      $modelName
     * @param string|null $modelVersion
     */
    public function __construct(string $rawJson, string $modelName, ?string $modelVersion = null)
    {
        if (trim($rawJson) === '') {
            throw new InvalidArgumentException('RawOcrResult: rawJson cannot be empty');
        }
        if (trim($modelName) === '') {
            throw new InvalidArgumentException('RawOcrResult: modelName cannot be empty');
        }

        // Validate JSON structure at construction time.
        $decoded = json_decode($rawJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException(
                'RawOcrResult: rawJson is not valid JSON – ' . json_last_error_msg()
            );
        }

        $this->rawJson      = $rawJson;
        $this->modelName    = $modelName;
        $this->modelVersion = $modelVersion;
    }

    /** @BABOK FR-007 / BR-004 / ARCH-010 — method: getRawJson */
    public function getRawJson(): string
    {
        return $this->rawJson;
    }

    /**
 * @BABOK FR-007 / BR-004 / ARCH-010 — method: decode
     * Decode and return the JSON payload as a PHP array.
     *
     * @return array
     */
    public function decode(): array
    {
        return (array) json_decode($this->rawJson, true);
    }

    /** @BABOK FR-007 / BR-004 / ARCH-010 — method: getModelName */
    public function getModelName(): string
    {
        return $this->modelName;
    }

    /** @BABOK FR-007 / BR-004 / ARCH-010 — method: getModelVersion */
    public function getModelVersion(): ?string
    {
        return $this->modelVersion;
    }
}
