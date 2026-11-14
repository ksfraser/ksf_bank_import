<?php

declare(strict_types=1);
// @BABOK RTM: FR-007 / BR-004 / ARCH-010 (domain layer)
/** @BABOK RTM: FR-007 / BR-004 / ARCH-010*/

namespace Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject;

use InvalidArgumentException;

/**
 * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001

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
 * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
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

    /** * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
*/
    public function getRawJson(): string
    {
        return $this->rawJson;
    }

    /**
 * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
     * Decode and return the JSON payload as a PHP array.
     *
     * @return array
     */
    public function decode(): array
    {
        return (array) json_decode($this->rawJson, true);
    }

    /** * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
*/
    public function getModelName(): string
    {
        return $this->modelName;
    }

    /** * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
 * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
*/
    public function getModelVersion(): ?string
    {
        return $this->modelVersion;
    }
}
