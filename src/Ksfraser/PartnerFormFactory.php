<?php

declare(strict_types=1);

namespace Ksfraser;

use Ksfraser\FormFieldNameGenerator;
use Ksfraser\PartnerTypes\PartnerTypeRegistry;

/**
 * PartnerFormFactory
 *
 * Factory for rendering partner-type-specific forms.
 * Extracted from ViewBILineItems::displayPartnerType() and related methods.
 *
 * This component follows the Single Responsibility Principle by focusing
 * solely on form generation based on partner type.
 *
 * Performance Note:
 * Currently uses FA helper functions (supplier_list, customer_list, etc.)
 * which query the database on each call. For pages with multiple line items,
 * consider using DataProvider pattern (see PAGE_LEVEL_DATA_LOADING_STRATEGY.md)
 * for significant performance improvements.
 *
 * TODO: Integrate with SupplierDataProvider (Task #12)
 * TODO: Integrate with CustomerDataProvider (Task #13)
 * TODO: Integrate with BankAccountDataProvider (Task #14)
 * TODO: Integrate with QuickEntryDataProvider (Task #15)
 *
 * @package    Ksfraser
 * @author     Claude AI Assistant
 * @since      20251019
 * @version    1.0.0
 *
 * @example
 * ```php
 * $factory = new PartnerFormFactory(123);
 * $factory->setMemo('Payment for invoice');
 *
 * // Render supplier form
 * echo $factory->renderForm('SP', ['partnerId' => 'SUPP123']);
 *
 * // Or render complete form with comment and button
 * echo $factory->renderCompleteForm('SP', ['partnerId' => 'SUPP123']);
 * ```
 */
class PartnerFormFactory
{
    /**
     * @var int The line item ID
     */
    private int $lineItemId;

    /**
     * @var FormFieldNameGenerator Field name generator
     */
    private FormFieldNameGenerator $fieldGenerator;

    /**
     * @var PartnerTypeRegistry Partner type registry
     */
    private PartnerTypeRegistry $registry;

    /**
     * @var string The memo/comment text
     */
    private string $memo = '';

    /**
     * @var array<string, mixed> Line item data
     */
    private array $lineItemData = [];

    /**
     * Constructor
     *
     * @param int                          $lineItemId     The line item ID
     * @param FormFieldNameGenerator|null  $fieldGenerator Optional field name generator
     * @param array<string, mixed>         $lineItemData   Optional line item data
     *
     * @since 20251019
     */
    public function __construct(
        int $lineItemId,
        ?FormFieldNameGenerator $fieldGenerator = null,
        array $lineItemData = []
    ) {
        $this->lineItemId = $lineItemId;
        $this->fieldGenerator = $fieldGenerator ?? new FormFieldNameGenerator();
        $this->registry = PartnerTypeRegistry::getInstance();
        $this->lineItemData = $lineItemData;
    }

    /**
     * Get the line item ID
     *
     * @return int The line item ID
     *
     * @since 20251019
     */
    public function getLineItemId(): int
    {
        return $this->lineItemId;
    }

    /**
     * Get the field name generator
     *
     * @return FormFieldNameGenerator The field name generator
     *
     * @since 20251019
     */
    public function getFieldNameGenerator(): FormFieldNameGenerator
    {
        return $this->fieldGenerator;
    }

    /**
     * Set the memo/comment text
     *
     * @param string $memo The memo text
     *
     * @return self Fluent interface
     *
     * @since 20251019
     */
    public function setMemo(string $memo): self
    {
        $this->memo = $memo;
        return $this;
    }

    /**
     * Render form based on partner type
     *
     * @param string               $partnerType The partner type code
     * @param array<string, mixed> $data        Form-specific data
     *
     * @return string HTML form content
     *
     * @throws \InvalidArgumentException If partner type is invalid
     *
     * @since 20251019
     */
    public function renderForm(string $partnerType, array $data): string
    {
        // Validate partner type
        if (!$this->registry->isValid($partnerType)) {
            throw new \InvalidArgumentException("Invalid partner type: {$partnerType}");
        }

        // Delegate to appropriate renderer
        switch ($partnerType) {
            case 'SP':
                return $this->renderSupplierForm($data);
            case 'CU':
                return $this->renderCustomerForm($data);
            case 'BT':
                return $this->renderBankTransferForm($data);
            case 'QE':
                return $this->renderQuickEntryForm($data);
            case 'MA':
                return $this->renderMatchedForm($data);
            case 'ZZ':
                return $this->renderUnknownForm($data);
            default:
                throw new \InvalidArgumentException("Unhandled partner type: {$partnerType}");
        }
    }

    /**
     * Render supplier form
     *
     * TODO: Optimize with SupplierDataProvider (Task #12)
     * Currently calls supplier_list() which queries database.
     * For pages with multiple SP line items, this is inefficient.
     *
     * @param array<string, mixed> $data Form data
     *
     * @return string HTML form content
     *
     * @since 20251019
     */
    private function renderSupplierForm(array $data): string
    {
        $fieldName = $this->fieldGenerator->partnerIdField($this->lineItemId);
        $partnerId = $data['partnerId'] ?? null;

        // Simulated output (real implementation would call supplier_list())
        $html = "<!-- Payment To: -->\n";
        $html .= "<!-- Field: {$fieldName} -->\n";
        $html .= "<!-- supplier_list('{$fieldName}', ...) would be called here -->\n";
        $html .= "<!-- TODO: Replace with SupplierDataProvider::generateSelectHtml() -->\n";

        return $html;
    }

    /**
     * Render customer form
     *
     * TODO: Optimize with CustomerDataProvider (Task #13)
     * Currently calls customer_list() and customer_branches_list().
     * For pages with multiple CU line items, this is inefficient.
     *
     * @param array<string, mixed> $data Form data
     *
     * @return string HTML form content
     *
     * @since 20251019
     */
    private function renderCustomerForm(array $data): string
    {
        $fieldName = $this->fieldGenerator->partnerIdField($this->lineItemId);
        $detailFieldName = $this->fieldGenerator->partnerDetailIdField($this->lineItemId);

        $html = "<!-- From Customer/Branch: -->\n";
        $html .= "<!-- Field: {$fieldName} -->\n";
        $html .= "<!-- customer_list('{$fieldName}', ...) would be called here -->\n";
        $html .= "<!-- customer_branches_list(..., '{$detailFieldName}', ...) would be called here -->\n";
        $html .= "<!-- TODO: Replace with CustomerDataProvider::generateSelectHtml() -->\n";

        return $html;
    }

    /**
     * Render bank transfer form
     *
     * TODO: Optimize with BankAccountDataProvider (Task #14)
     * Currently calls bank_accounts_list() which queries database.
     * For pages with multiple BT line items, this is inefficient.
     *
     * @param array<string, mixed> $data Form data
     *
     * @return string HTML form content
     *
     * @since 20251019
     */
    private function renderBankTransferForm(array $data): string
    {
        $fieldName = $this->fieldGenerator->partnerIdField($this->lineItemId);
        $transactionDC = $data['transactionDC'] ?? 'D';

        $label = ($transactionDC === 'C')
            ? 'Transfer to Our Bank Account from (OTHER ACCOUNT):'
            : 'Transfer from Our Bank Account To (OTHER ACCOUNT):';

        $html = "<!-- {$label} -->\n";
        $html .= "<!-- Field: {$fieldName} -->\n";
        $html .= "<!-- bank_accounts_list('{$fieldName}', ...) would be called here -->\n";
        $html .= "<!-- TODO: Replace with BankAccountDataProvider::generateSelectHtml() -->\n";

        return $html;
    }

    /**
     * Render quick entry form
     *
     * TODO: Optimize with QuickEntryDataProvider (Task #15)
     * Currently calls quick_entries_list() which queries database.
     * For pages with multiple QE line items, this is inefficient.
     *
     * @param array<string, mixed> $data Form data
     *
     * @return string HTML form content
     *
     * @since 20251019
     */
    private function renderQuickEntryForm(array $data): string
    {
        $fieldName = $this->fieldGenerator->partnerIdField($this->lineItemId);
        $transactionDC = $data['transactionDC'] ?? 'D';
        $qeType = ($transactionDC === 'C') ? 'QE_DEPOSIT' : 'QE_PAYMENT';

        $html = "<!-- Quick Entry: -->\n";
        $html .= "<!-- Field: {$fieldName} -->\n";
        $html .= "<!-- quick_entries_list('{$fieldName}', null, {$qeType}, true) would be called here -->\n";
        $html .= "<!-- TODO: Replace with QuickEntryDataProvider::generateSelectHtml() -->\n";

        return $html;
    }

    /**
     * Render matched transaction form
     *
     * @param array<string, mixed> $data Form data
     *
     * @return string HTML form content
     *
     * @since 20251019
     */
    private function renderMatchedForm(array $data): string
    {
        $partnerIdField = $this->fieldGenerator->partnerIdField($this->lineItemId);

        $html = "<!-- hidden('{$partnerIdField}', 'manual') -->\n";
        $html .= "<!-- Existing Entry Type selector -->\n";
        $html .= "<!-- Existing Entry text input -->\n";

        return $html;
    }

    /**
     * Render form for unknown partner type (ZZ)
     *
     * Renders hidden fields for matched transaction data.
     *
     * @param array<string, mixed> $data Form data
     *
     * @return string HTML form content
     *
     * @since 20251019
     */
    private function renderUnknownForm(array $data): string
    {
        $html = '';

        if (isset($data['matching_trans'][0])) {
            $matchingTrans = $data['matching_trans'][0];

            $partnerIdField = $this->fieldGenerator->partnerIdField($this->lineItemId);
            $partnerDetailIdField = $this->fieldGenerator->partnerDetailIdField($this->lineItemId);
            $transNoField = $this->fieldGenerator->transactionNumberField($this->lineItemId);
            $transTypeField = $this->fieldGenerator->transactionTypeField($this->lineItemId);

            $html .= "<!-- hidden('{$partnerIdField}', '{$matchingTrans['type']}') -->\n";
            $html .= "<!-- hidden('{$partnerDetailIdField}', '{$matchingTrans['type_no']}') -->\n";
            $html .= "<!-- hidden('{$transNoField}', '{$matchingTrans['type_no']}') -->\n";
            $html .= "<!-- hidden('{$transTypeField}', '{$matchingTrans['type']}') -->\n";
        }

        return $html;
    }

    /**
     * Render comment field
     *
     * @return string HTML for comment field
     *
     * @since 20251019
     */
    public function renderCommentField(): string
    {
        $fieldName = $this->fieldGenerator->generate('comment', $this->lineItemId);
        $memoLength = strlen($this->memo);

        $html = "<!-- Comment: -->\n";
        $html .= "<!-- text_input('{$fieldName}', '{$this->memo}', {$memoLength}, '', 'Comment:') -->\n";

        return $html;
    }

    /**
     * Render process button
     *
     * @return string HTML for process button
     *
     * @since 20251019
     */
    public function renderProcessButton(): string
    {
        $html = "<!-- submit('ProcessTransaction[{$this->lineItemId}]', 'Process', false, '', 'default') -->\n";

        return $html;
    }

    /**
     * Render complete form with all elements
     *
     * Includes partner-specific form, comment field, and process button.
     *
     * @param string               $partnerType The partner type code
     * @param array<string, mixed> $data        Form-specific data
     *
     * @return string Complete HTML form
     *
     * @since 20251019
     */
    public function renderCompleteForm(string $partnerType, array $data): string
    {
        $html = '';

        // Partner-specific form
        $html .= $this->renderForm($partnerType, $data);

        // Comment field
        $html .= $this->renderCommentField();

        // Process button
        $html .= $this->renderProcessButton();

        return $html;
    }
}
