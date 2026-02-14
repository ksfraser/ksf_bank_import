<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :TransactionResult [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for TransactionResult.
 */
/**
 * Transaction Result
 *
 * Represents the outcome of a transaction processing operation.
 * Provides a clean interface for success/failure with display capabilities.
 *
 * Design inspired by Exception pattern but for non-exceptional outcomes.
 * Integrates with FrontAccounting's display_notification() system.
 *
 * @package    Ksfraser\FaBankImport\Results
 * @author     Original Author
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251020
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Results;

/**
 * Transaction Result
 *
 * Immutable value object representing transaction processing outcome.
 * Can be converted to array for backward compatibility or rendered to HTML.
 *
 * Example usage:
 * ```php
 * // Success
 * $result = TransactionResult::success(
 *     transNo: 42,
 *     transType: ST_SUPPAYMENT,
 *     message: 'Supplier payment processed',
 *     data: ['charge' => 5.00, 'reference' => 'REF-001']
 * );
 *
 * if ($result->isSuccess()) {
 *     $result->display();  // Shows green notification in FA
 * }
 *
 * // Error
 * $result = TransactionResult::error('Partner ID not found');
 * $result->display();  // Shows red error in FA
 *
 * // Warning
 * $result = TransactionResult::warning('Transaction already processed');
 * $result->display();  // Shows yellow warning in FA
 *
 * // Backward compatibility
 * $array = $result->toArray();  // Returns ['success' => true, 'trans_no' => 42, ...]
 * ```
 */
class TransactionResult
{
    /**
     * Success status
     *
     * @var bool
     */
    private bool $success;

    /**
     * Transaction number (0 if not applicable)
     *
     * @var int
     */
    private int $transNo;

    /**
     * Transaction type constant (e.g., ST_SUPPAYMENT)
     *
     * @var int
     */
    private int $transType;

    /**
     * Result message
     *
     * @var string
     */
    private string $message;

    /**
     * Display level (for FrontAccounting display_notification)
     *
     * @var string ('success', 'error', 'warning')
     */
    private string $level;

    /**
     * Additional data
     *
     * @var array<string, mixed>
     */
    private array $data;

    /**
     * Private constructor (use static factory methods)
     *
     * @param bool $success Success status
     * @param int $transNo Transaction number
     * @param int $transType Transaction type
     * @param string $message Result message
     * @param string $level Display level
     * @param array<string, mixed> $data Additional data
     */
    private function __construct(
        bool $success,
        int $transNo,
        int $transType,
        string $message,
        string $level,
        array $data = []
    ) {
        $this->success = $success;
        $this->transNo = $transNo;
        $this->transType = $transType;
        $this->message = $message;
        $this->level = $level;
        $this->data = $data;
    }

    /**
     * Create a success result
     *
     * @param int $transNo Transaction number
     * @param int $transType Transaction type constant
     * @param string $message Success message
     * @param array<string, mixed> $data Additional data
     * @return self
     */
    public static function success(
        int $transNo,
        int $transType,
        string $message,
        array $data = []
    ): self {
        return new self(
            true,      // success
            $transNo,
            $transType,
            $message,
            'success', // level
            $data
        );
    }

    /**
     * Create an error result
     *
     * @param string $message Error message
     * @param array<string, mixed> $data Additional data
     * @return self
     */
    public static function error(string $message, array $data = []): self
    {
        return new self(
            false,    // success
            0,        // transNo
            0,        // transType
            $message,
            'error',  // level
            $data
        );
    }

    /**
     * Create a warning result
     *
     * @param string $message Warning message
     * @param int $transNo Transaction number (if applicable)
     * @param int $transType Transaction type (if applicable)
     * @param array<string, mixed> $data Additional data
     * @return self
     */
    public static function warning(
        string $message,
        int $transNo = 0,
        int $transType = 0,
        array $data = []
    ): self {
        return new self(
            false,     // success
            $transNo,
            $transType,
            $message,
            'warning', // level
            $data
        );
    }

    /**
     * Check if result is successful
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Check if result is an error
     *
     * @return bool
     */
    public function isError(): bool
    {
        return !$this->success && $this->level === 'error';
    }

    /**
     * Check if result is a warning
     *
     * @return bool
     */
    public function isWarning(): bool
    {
        return $this->level === 'warning';
    }

    /**
     * Get transaction number
     *
     * @return int
     */
    public function getTransNo(): int
    {
        return $this->transNo;
    }

    /**
     * Get transaction type
     *
     * @return int
     */
    public function getTransType(): int
    {
        return $this->transType;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Get display level
     *
     * @return string
     */
    public function getLevel(): string
    {
        return $this->level;
    }

    /**
     * Get additional data
     *
     * @param string|null $key Specific key to retrieve, or null for all data
     * @return array|mixed
     */
    public function getData(?string $key = null)
    {
        if ($key === null) {
            return $this->data;
        }

        return $this->data[$key] ?? null;
    }

    /**
     * Convert to array (backward compatibility)
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge(
            [
                'success' => $this->success,
                'trans_no' => $this->transNo,
                'trans_type' => $this->transType,
                'message' => $this->message,
                'level' => $this->level,
            ],
            $this->data
        );
    }

    /**
     * Display result using FrontAccounting's notification system
     *
     * Calls display_notification() with appropriate level:
     * - success: Green banner at top of screen
     * - warning: Yellow banner at top of screen
     * - error: Red banner at top of screen (via display_error)
     *
     * @return void
     */
    public function display(): void
    {
        if ($this->level === 'error') {
            // FrontAccounting's display_error() for red banner
            if (function_exists('display_error')) {
                display_error($this->message);
            }
        } elseif ($this->level === 'warning') {
            // FrontAccounting's display_warning() for yellow banner
            if (function_exists('display_warning')) {
                display_warning($this->message);
            } elseif (function_exists('display_notification')) {
                // Fallback if display_warning doesn't exist
                display_notification($this->message);
            }
        } else {
            // FrontAccounting's display_notification() for green banner (success)
            if (function_exists('display_notification')) {
                display_notification($this->message);
            }
        }
    }

    /**
     * Get HTML representation of result
     *
     * Returns HTML div with appropriate Bootstrap alert class.
     * Useful for AJAX responses or non-FA contexts.
     *
     * @return string
     */
    public function toHtml(): string
    {
        // Map level to Bootstrap alert class
        $alertClassMap = [
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
        ];
        $alertClass = $alertClassMap[$this->level] ?? 'alert-info';

        // Map level to icon
        $iconMap = [
            'success' => '✓',
            'error' => '✗',
            'warning' => '⚠',
        ];
        $icon = $iconMap[$this->level] ?? 'ℹ';

        $html = sprintf(
            '<div class="alert %s" role="alert">',
            htmlspecialchars($alertClass, ENT_QUOTES, 'UTF-8')
        );

        $html .= sprintf(
            '<strong>%s</strong> %s',
            $icon,
            htmlspecialchars($this->message, ENT_QUOTES, 'UTF-8')
        );

        // Add transaction details if available
        if ($this->transNo > 0) {
            $html .= sprintf(
                '<br><small>Transaction #%d (Type: %d)</small>',
                $this->transNo,
                $this->transType
            );
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Convert to string (returns message)
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->message;
    }

    /**
     * Create result from array (backward compatibility)
     *
     * Allows conversion from old array format to TransactionResult object.
     *
     * @param array<string, mixed> $array Result array
     * @return self
     */
    public static function fromArray(array $array): self
    {
        $success = $array['success'] ?? false;
        $transNo = $array['trans_no'] ?? 0;
        $transType = $array['trans_type'] ?? 0;
        $message = $array['message'] ?? '';

        // Determine level from success status and message
        $level = $array['level'] ?? ($success ? 'success' : 'error');

        // Extract additional data (remove standard keys)
        $data = array_diff_key($array, array_flip([
            'success',
            'trans_no',
            'trans_type',
            'message',
            'level'
        ]));

        return new self(
            $success,
            (int)$transNo,
            (int)$transType,
            $message,
            $level,
            $data
        );
    }
}
