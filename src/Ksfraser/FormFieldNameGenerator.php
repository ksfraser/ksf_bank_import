<?php

declare(strict_types=1);

namespace Ksfraser;

/**
 * FormFieldNameGenerator
 *
 * Generates consistent, standardized form field names for HTML forms.
 * Provides methods for common field patterns used throughout the application.
 *
 * This class follows the Single Responsibility Principle by focusing solely
 * on field name generation with configurable separators and sanitization.
 *
 * @package    Ksfraser
 * @author     Claude AI Assistant
 * @since      20251019
 * @version    1.0.0
 *
 * @example
 * ```php
 * $generator = new FormFieldNameGenerator();
 * echo $generator->partnerIdField(123);        // "partnerId_123"
 * echo $generator->generate('vendor_id', 456); // "vendor_id_456"
 *
 * $custom = new FormFieldNameGenerator('-');
 * echo $custom->generate('field', 789);        // "field-789"
 * ```
 */
class FormFieldNameGenerator
{
    /**
     * @var string The separator character used between field name and ID
     */
    private string $separator;

    /**
     * Constructor
     *
     * @param string $separator The separator character (default: '_')
     *
     * @since 20251019
     */
    public function __construct(string $separator = '_')
    {
        $this->separator = $separator;
    }

    /**
     * Generate a field name with optional ID suffix
     *
     * @param string   $field The base field name
     * @param int|null $id    Optional ID to append (default: null)
     *
     * @return string The generated field name
     *
     * @since 20251019
     *
     * @example
     * ```php
     * $generator->generate('vendor_id');       // "vendor_id"
     * $generator->generate('vendor_id', 123);  // "vendor_id_123"
     * ```
     */
    public function generate(string $field, ?int $id = null): string
    {
        $sanitized = $this->sanitize($field);

        if ($id === null) {
            return $sanitized;
        }

        return $sanitized . $this->separator . $id;
    }

    /**
     * Generate a field name with ID prefix
     *
     * @param string $field The base field name
     * @param int    $id    The ID to prefix
     *
     * @return string The generated field name with ID prefix
     *
     * @since 20251019
     *
     * @example
     * ```php
     * $generator->generateWithPrefix('vendor_id', 123);  // "123_vendor_id"
     * ```
     */
    public function generateWithPrefix(string $field, int $id): string
    {
        $sanitized = $this->sanitize($field);
        return $id . $this->separator . $sanitized;
    }

    /**
     * Generate a partner ID field name
     *
     * @param int $id The partner ID
     *
     * @return string The partner ID field name
     *
     * @since 20251019
     *
     * @example
     * ```php
     * $generator->partnerIdField(456);  // "partnerId_456"
     * ```
     */
    public function partnerIdField(int $id): string
    {
        return $this->generate('partnerId', $id);
    }

    /**
     * Generate a partner detail ID field name
     *
     * @param int $id The partner detail ID
     *
     * @return string The partner detail ID field name
     *
     * @since 20251019
     *
     * @example
     * ```php
     * $generator->partnerDetailIdField(789);  // "partnerDetailId_789"
     * ```
     */
    public function partnerDetailIdField(int $id): string
    {
        return $this->generate('partnerDetailId', $id);
    }

    /**
     * Generate a partner type field name
     *
     * @param int $id The partner type ID
     *
     * @return string The partner type field name
     *
     * @since 20251019
     *
     * @example
     * ```php
     * $generator->partnerTypeField(123);  // "partnerType_123"
     * ```
     */
    public function partnerTypeField(int $id): string
    {
        return $this->generate('partnerType', $id);
    }

    /**
     * Generate a vendor short field name
     *
     * @param int $id The vendor ID
     *
     * @return string The vendor short field name
     *
     * @since 20251019
     *
     * @example
     * ```php
     * $generator->vendorShortField(100);  // "vendor_short_100"
     * ```
     */
    public function vendorShortField(int $id): string
    {
        return $this->generate('vendor_short', $id);
    }

    /**
     * Generate a vendor long field name
     *
     * @param int $id The vendor ID
     *
     * @return string The vendor long field name
     *
     * @since 20251019
     *
     * @example
     * ```php
     * $generator->vendorLongField(200);  // "vendor_long_200"
     * ```
     */
    public function vendorLongField(int $id): string
    {
        return $this->generate('vendor_long', $id);
    }

    /**
     * Generate a transaction number field name
     *
     * @param int $id The transaction ID
     *
     * @return string The transaction number field name
     *
     * @since 20251019
     *
     * @example
     * ```php
     * $generator->transactionNumberField(50);  // "trans_no_50"
     * ```
     */
    public function transactionNumberField(int $id): string
    {
        return $this->generate('trans_no', $id);
    }

    /**
     * Generate a transaction type field name
     *
     * @param int $id The transaction type ID
     *
     * @return string The transaction type field name
     *
     * @since 20251019
     *
     * @example
     * ```php
     * $generator->transactionTypeField(75);  // "trans_type_75"
     * ```
     */
    public function transactionTypeField(int $id): string
    {
        return $this->generate('trans_type', $id);
    }

    /**
     * Sanitize a field name by replacing spaces and hyphens with underscores
     *
     * @param string $field The field name to sanitize
     *
     * @return string The sanitized field name
     *
     * @since 20251019
     *
     * @example
     * ```php
     * $generator->sanitize('my field');     // "my_field"
     * $generator->sanitize('my-field');     // "my_field"
     * $generator->sanitize('my field-name'); // "my_field_name"
     * ```
     */
    public function sanitize(string $field): string
    {
        return str_replace([' ', '-'], '_', $field);
    }

    /**
     * Generate multiple field names with the same ID
     *
     * @param array $fields Array of field names
     * @param int   $id     The ID to append to each field
     *
     * @return array Array of generated field names
     *
     * @since 20251019
     *
     * @example
     * ```php
     * $generator->generateMultiple(['vendor_id', 'customer_id'], 100);
     * // ["vendor_id_100", "customer_id_100"]
     * ```
     */
    public function generateMultiple(array $fields, int $id): array
    {
        return array_map(
            function (string $field) use ($id): string {
                return $this->generate($field, $id);
            },
            $fields
        );
    }

    /**
     * Get the current separator character
     *
     * @return string The separator character
     *
     * @since 20251019
     */
    public function getSeparator(): string
    {
        return $this->separator;
    }
}
