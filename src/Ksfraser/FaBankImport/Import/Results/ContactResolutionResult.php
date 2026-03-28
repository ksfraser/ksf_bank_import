<?php

namespace Ksfraser\FaBankImport\Import\Results;

/**
 * Result of contact resolution/linking.
 * 
 * Provides contact ID, type, and how it was resolved (auto/manual lookup).
 */
class ContactResolutionResult extends OperationResult
{
    /**
     * @var int|null Contact ID resolved
     */
    private ?int $contactId = null;

    /**
     * @var string|null Contact type (CU|DE|SU|VE|EM|BR)
     */
    private ?string $contactType = null;

    /**
     * @var string Resolution method (auto|manual|skipped|created)
     */
    private string $resolutionMethod = 'skipped';

    /**
     * @var bool Whether contact was auto-matched
     */
    private bool $wasAutoMatched = false;

    /**
     * Create a resolved contact result.
     *
     * @param int $contactId
     * @param string $contactType
     * @param string $method auto|manual|created
     * @return self
     */
    public static function resolved(int $contactId, string $contactType, string $method = 'auto'): self
    {
        $result = new self();
        $result->success = true;
        $result->contactId = $contactId;
        $result->contactType = $contactType;
        $result->resolutionMethod = $method;
        $result->wasAutoMatched = ($method === 'auto');
        return $result;
    }

    /**
     * Create a skipped contact result (no resolution).
     *
     * @param string $reason
     * @return self
     */
    public static function skipped(string $reason): self
    {
        $result = new self();
        $result->success = false;
        $result->resolutionMethod = 'skipped';
        $result->errors[] = "Contact not resolved: {$reason}";
        return $result;
    }

    /**
     * Check if contact was resolved.
     *
     * @return bool
     */
    public function wasResolved(): bool
    {
        return $this->contactId !== null;
    }

    /**
     * Get contact ID.
     *
     * @return int|null
     */
    public function getContactId(): ?int
    {
        return $this->contactId;
    }

    /**
     * Get contact type.
     *
     * @return string|null
     */
    public function getContactType(): ?string
    {
        return $this->contactType;
    }

    /**
     * Get resolution method.
     *
     * @return string
     */
    public function getResolutionMethod(): string
    {
        return $this->resolutionMethod;
    }

    /**
     * Check if contact was auto-matched.
     *
     * @return bool
     */
    public function wasAutoMatched(): bool
    {
        return $this->wasAutoMatched;
    }
}
