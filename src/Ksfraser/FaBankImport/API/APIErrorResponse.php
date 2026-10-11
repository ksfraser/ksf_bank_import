<?php

/**
 * API Error Response DTO
 *
 * Standardized error response format for API failures.
 * Contains status code, message, and optional details.
 *
 * @author Kevin Fraser
 * @since 2.4.0
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\API;

/**
 * APIErrorResponse
 *
 * Response object for API errors.
 * Provides consistent error information format.
 */
class APIErrorResponse
{
    /**
     * Constructor
     *
     * @param int $statusCode HTTP status code
     * @param string $message Error message
     * @param string|null $code Error code (optional)
     * @param array|null $details Additional error details (optional)
     */
    public function __construct(
        private readonly int $statusCode,
        private readonly string $message,
        private readonly ?string $code = null,
        private readonly ?array $details = null
    ) {
    }

    /**
     * Get status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get message
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Get error code
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * Get error details
     */
    public function getDetails(): ?array
    {
        return $this->details;
    }

    /**
     * Convert to array for JSON serialization
     */
    public function toArray(): array
    {
        $error = [
            'status_code' => $this->statusCode,
            'message' => $this->message,
        ];

        if ($this->code !== null) {
            $error['code'] = $this->code;
        }

        if ($this->details !== null) {
            $error['details'] = $this->details;
        }

        return [
            'error' => $error,
        ];
    }
}
