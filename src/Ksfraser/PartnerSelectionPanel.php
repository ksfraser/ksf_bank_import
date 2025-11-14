<?php

declare(strict_types=1);

namespace Ksfraser;

use Ksfraser\FormFieldNameGenerator;
use Ksfraser\PartnerTypes\PartnerTypeRegistry;

/**
 * PartnerSelectionPanel
 *
 * Generates HTML for partner type selection dropdown.
 * Encapsulates the partner type selector logic extracted from ViewBILineItems.
 *
 * This component follows the Single Responsibility Principle by focusing
 * solely on rendering the partner type selection UI element.
 *
 * Performance Optimization:
 * When displaying multiple line items on a page, use the static 
 * getPartnerTypesArray() method once at page level to avoid regenerating
 * the same partner types array for each line item.
 *
 * @package    Ksfraser
 * @author     Claude AI Assistant
 * @since      20251019
 * @version    1.1.0
 *
 * @example
 * ```php
 * // Single line item:
 * $panel = new PartnerSelectionPanel(123, 'SP');
 * echo $panel->getHtml();
 *
 * // Multiple line items (optimized):
 * $optypes = PartnerSelectionPanel::getPartnerTypesArray(); // Once per page
 * foreach ($lineItems as $item) {
 *     $panel = new PartnerSelectionPanel($item->id, $item->partnerType);
 *     // Panel uses cached types internally
 *     echo $panel->getHtml();
 * }
 * ```
 */
class PartnerSelectionPanel
{
    /**
     * @var array<string, string>|null Cached partner types array
     */
    private static ?array $cachedPartnerTypes = null;

    /**
     * @var int The line item ID
     */
    private int $id;

    /**
     * @var string The selected partner type code
     */
    private string $selectedType;

    /**
     * @var FormFieldNameGenerator Field name generator
     */
    private FormFieldNameGenerator $fieldGenerator;

    /**
     * @var PartnerTypeRegistry Partner type registry
     */
    private PartnerTypeRegistry $registry;

    /**
     * @var string The label text for the selector
     */
    private string $label = 'Partner:';

    /**
     * @var bool Whether to enable select_submit
     */
    private bool $selectSubmit = true;

    /**
     * Constructor
     *
     * @param int                          $id             The line item ID
     * @param string                       $selectedType   The currently selected partner type code
     * @param FormFieldNameGenerator|null  $fieldGenerator Optional field name generator
     * @param PartnerTypeRegistry|null     $registry       Optional partner type registry
     *
     * @throws \InvalidArgumentException If selected type is invalid
     *
     * @since 20251019
     */
    public function __construct(
        int $id,
        string $selectedType,
        ?FormFieldNameGenerator $fieldGenerator = null,
        ?PartnerTypeRegistry $registry = null
    ) {
        $this->id = $id;
        $this->fieldGenerator = $fieldGenerator ?? new FormFieldNameGenerator();
        $this->registry = $registry ?? PartnerTypeRegistry::getInstance();

        // Validate selected type
        if (!$this->registry->isValid($selectedType)) {
            throw new \InvalidArgumentException(
                "Invalid partner type code: {$selectedType}"
            );
        }

        $this->selectedType = $selectedType;
    }

    /**
     * Get the field name for the partner type selector
     *
     * @return string The generated field name
     *
     * @since 20251019
     */
    public function getFieldName(): string
    {
        return $this->fieldGenerator->partnerTypeField($this->id);
    }

    /**
     * Get all available partner types as code => label array
     *
     * This method uses internal caching to avoid regenerating the array
     * on every call.
     *
     * @return array<string, string> Partner type codes and labels
     *
     * @since 20251019
     */
    public function getPartnerTypes(): array
    {
        return self::getPartnerTypesArray($this->registry);
    }

    /**
     * Get partner types array (static cached version)
     *
     * This static method generates the partner types array once and caches it.
     * Use this at page level when displaying multiple line items to avoid
     * regenerating the same array multiple times.
     *
     * @param PartnerTypeRegistry|null $registry Optional custom registry
     *
     * @return array<string, string> Partner type codes and labels
     *
     * @since 20251019
     *
     * @example
     * ```php
     * // At page level (once per request):
     * $optypes = PartnerSelectionPanel::getPartnerTypesArray();
     * 
     * // Then use in multiple line items:
     * foreach ($items as $item) {
     *     $panel = new PartnerSelectionPanel($item->id, $item->type);
     *     // Uses cached array internally
     * }
     * ```
     */
    public static function getPartnerTypesArray(?PartnerTypeRegistry $registry = null): array
    {
        // Return cached version if available and no custom registry
        if (self::$cachedPartnerTypes !== null && $registry === null) {
            return self::$cachedPartnerTypes;
        }

        // Use provided registry or get default instance
        $reg = $registry ?? PartnerTypeRegistry::getInstance();

        // Build the types array
        $types = [];
        foreach ($reg->getAll() as $partnerType) {
            $types[$partnerType->getShortCode()] = $partnerType->getLabel();
        }

        // Cache if using default registry
        if ($registry === null) {
            self::$cachedPartnerTypes = $types;
        }

        return $types;
    }

    /**
     * Clear the cached partner types array
     *
     * Useful for testing or when partner types are modified at runtime.
     *
     * @return void
     *
     * @since 20251019
     */
    public static function clearCache(): void
    {
        self::$cachedPartnerTypes = null;
    }

    /**
     * Get the currently selected partner type code
     *
     * @return string The selected partner type code
     *
     * @since 20251019
     */
    public function getSelectedType(): string
    {
        return $this->selectedType;
    }

    /**
     * Set the selected partner type
     *
     * @param string $code The partner type code
     *
     * @return self Fluent interface
     *
     * @throws \InvalidArgumentException If code is invalid
     *
     * @since 20251019
     */
    public function setSelectedType(string $code): self
    {
        if (!$this->registry->isValid($code)) {
            throw new \InvalidArgumentException(
                "Invalid partner type code: {$code}"
            );
        }

        $this->selectedType = $code;
        return $this;
    }

    /**
     * Get the array_selector options
     *
     * @return array<string, mixed> Options for array_selector
     *
     * @since 20251019
     */
    public function getArraySelectorOptions(): array
    {
        return [
            'select_submit' => $this->selectSubmit,
        ];
    }

    /**
     * Set whether to enable select_submit
     *
     * @param bool $enabled Whether to enable select_submit
     *
     * @return self Fluent interface
     *
     * @since 20251019
     */
    public function setSelectSubmit(bool $enabled): self
    {
        $this->selectSubmit = $enabled;
        return $this;
    }

    /**
     * Get the label text
     *
     * @return string The label text
     *
     * @since 20251019
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Set the label text
     *
     * @param string $label The label text
     *
     * @return self Fluent interface
     *
     * @since 20251019
     */
    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Generate HTML for the partner type selector
     *
     * This generates a call to array_selector() compatible with the
     * existing FA codebase.
     *
     * @return string HTML for array_selector
     *
     * @since 20251019
     */
    public function getHtml(): string
    {
        $fieldName = $this->getFieldName();
        $selectedType = $this->getSelectedType();
        $types = $this->getPartnerTypes();
        $options = $this->getArraySelectorOptions();

        // Build the array_selector call
        $optionsJson = json_encode($options, JSON_UNESCAPED_SLASHES);

        $html = "array_selector(\"{$fieldName}\", \"{$selectedType}\", ";
        $html .= $this->buildTypesArray($types);
        $html .= ", {$optionsJson})";

        return $html;
    }

    /**
     * Build the types array as a string representation
     *
     * @param array<string, string> $types Partner types
     *
     * @return string String representation of types array
     *
     * @since 20251019
     */
    private function buildTypesArray(array $types): string
    {
        $items = [];
        foreach ($types as $code => $label) {
            $items[] = "'{$code}' => '{$label}'";
        }
        return 'array(' . implode(', ', $items) . ')';
    }

    /**
     * Get output suitable for label_row()
     *
     * Returns an array with 'label' and 'content' keys suitable
     * for use with label_row() function.
     *
     * @return array{label: string, content: string} Label and content for label_row
     *
     * @since 20251019
     */
    public function getLabelRowOutput(): array
    {
        return [
            'label' => $this->label,
            'content' => $this->getHtml(),
        ];
    }

    /**
     * Get the partner type registry instance
     *
     * @return PartnerTypeRegistry The registry instance
     *
     * @since 20251019
     */
    public function getRegistry(): PartnerTypeRegistry
    {
        return $this->registry;
    }
}
