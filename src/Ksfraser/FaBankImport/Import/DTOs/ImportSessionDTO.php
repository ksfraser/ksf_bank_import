<?php

namespace Ksfraser\FaBankImport\Import\DTOs;

/**
 * Data transfer object for import session state
 *
 * Tracks the complete state of an import session as it moves through
 * the pipeline. Contains all data and results from each stage.
 *
 * Immutable - use withXXX() methods to create updated versions.
 */
final class ImportSessionDTO
{
    /**
     * Create import session DTO
     *
     * @param string $sessionId Unique session identifier
     * @param int $uploadedFileId Reference to UploadedFile entity
     * @param string $fileName Original file name
     * @param string $step Current step in pipeline
     * @param string $status Overall status
     * @param ParsedStatementDTO|null $parsedData Parsed statement (null if not yet parsed)
     * @param ValidationResultDTO|null $validationResult Validation result (null if not yet validated)
     * @param array<int, mixed> $entitiesCreated Created domain entities
     * @param array<int, array<string, mixed>> $duplicatesFound Found duplicates
     * @param array<int, string> $errors Any errors encountered
     * @param int $createdAt Unix timestamp
     * @param int $updatedAt Unix timestamp
     */
    private function __construct(
        public readonly string $sessionId,
        public readonly int $uploadedFileId,
        public readonly string $fileName,
        public readonly string $step,
        public readonly string $status,
        public readonly ?ParsedStatementDTO $parsedData = null,
        public readonly ?ValidationResultDTO $validationResult = null,
        public readonly array $entitiesCreated = [],
        public readonly array $duplicatesFound = [],
        public readonly array $errors = [],
        public readonly int $createdAt = 0,
        public readonly int $updatedAt = 0
    ) {
    }

    /**
     * Create new import session
     *
     * @param string $sessionId Session identifier
     * @param int $uploadedFileId Reference to uploaded file
     * @param string $fileName Original file name
     * @return self
     */
    public static function create(
        string $sessionId,
        int $uploadedFileId,
        string $fileName
    ): self {
        $now = time();
        return new self(
            $sessionId,
            $uploadedFileId,
            $fileName,
            'uploaded',
            'in_progress',
            null,
            null,
            [],
            [],
            [],
            $now,
            $now
        );
    }

    /**
     * Create with new step
     *
     * @param string $newStep The new step
     * @return self
     */
    public function withStep(string $newStep): self
    {
        return new self(
            $this->sessionId,
            $this->uploadedFileId,
            $this->fileName,
            $newStep,
            $this->status,
            $this->parsedData,
            $this->validationResult,
            $this->entitiesCreated,
            $this->duplicatesFound,
            $this->errors,
            $this->createdAt,
            time()
        );
    }

    /**
     * Create with parsed data
     *
     * @param ParsedStatementDTO $parsedData
     * @return self
     */
    public function withParsedData(ParsedStatementDTO $parsedData): self
    {
        return new self(
            $this->sessionId,
            $this->uploadedFileId,
            $this->fileName,
            $this->step,
            $this->status,
            $parsedData,
            $this->validationResult,
            $this->entitiesCreated,
            $this->duplicatesFound,
            $this->errors,
            $this->createdAt,
            time()
        );
    }

    /**
     * Create with validation result
     *
     * @param ValidationResultDTO $validationResult
     * @return self
     */
    public function withValidationResult(ValidationResultDTO $validationResult): self
    {
        return new self(
            $this->sessionId,
            $this->uploadedFileId,
            $this->fileName,
            $this->step,
            $this->status,
            $this->parsedData,
            $validationResult,
            $this->entitiesCreated,
            $this->duplicatesFound,
            $this->errors,
            $this->createdAt,
            time()
        );
    }

    /**
     * Create with new entities
     *
     * @param array<int, mixed> $entities Created entities
     * @return self
     */
    public function withEntitiesCreated(array $entities): self
    {
        return new self(
            $this->sessionId,
            $this->uploadedFileId,
            $this->fileName,
            $this->step,
            $this->status,
            $this->parsedData,
            $this->validationResult,
            $entities,
            $this->duplicatesFound,
            $this->errors,
            $this->createdAt,
            time()
        );
    }

    /**
     * Create with new status and optional error
     *
     * @param string $newStatus New status (in_progress, success, error, cancelled)
     * @param array<int, string> $errors Optional errors
     * @return self
     */
    public function withStatus(string $newStatus, array $errors = []): self
    {
        return new self(
            $this->sessionId,
            $this->uploadedFileId,
            $this->fileName,
            $this->step,
            $newStatus,
            $this->parsedData,
            $this->validationResult,
            $this->entitiesCreated,
            $this->duplicatesFound,
            $errors ?: $this->errors,
            $this->createdAt,
            time()
        );
    }

    /**
     * Get session age in seconds
     *
     * @return int
     */
    public function getAgeSeconds(): int
    {
        return time() - $this->createdAt;
    }

    /**
     * Convert to array for storage/serialization
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'sessionId' => $this->sessionId,
            'uploadedFileId' => $this->uploadedFileId,
            'fileName' => $this->fileName,
            'step' => $this->step,
            'status' => $this->status,
            'parsedData' => $this->parsedData?->toArray(),
            'validationResult' => $this->validationResult?->toArray(),
            'entitiesCreated' => $this->entitiesCreated,
            'duplicatesFound' => $this->duplicatesFound,
            'errors' => $this->errors,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
