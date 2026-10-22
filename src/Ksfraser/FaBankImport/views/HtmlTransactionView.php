<?php

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\FaBankImport\Interfaces\TransactionViewInterface;
use Ksfraser\FaBankImport\Interfaces\BankTransactionInterface;

/**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
 */
class HtmlTransactionView implements TransactionViewInterface
{
    private $transaction;
    private $buttons = [];

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    public function __construct(BankTransactionInterface $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    public function render(): string
    {
        $html = '<div class="transaction">';
        $html .= $this->renderTransactionDetails();
        $html .= $this->renderActions();
        $html .= '</div>';
        return $html;
    }

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    private function renderTransactionDetails(): string
    {
        $accountDetails = $this->transaction->getAccountDetails();
        $otherPartyDetails = $this->transaction->getOtherPartyDetails();

        return sprintf(
            '<table class="details">
                <tr><td>Date:</td><td>%s</td></tr>
                <tr><td>Amount:</td><td>%s</td></tr>
                <tr><td>Account:</td><td>%s</td></tr>
                <tr><td>Other Party:</td><td>%s</td></tr>
                <tr><td>Memo:</td><td>%s</td></tr>
            </table>',
            htmlspecialchars($this->transaction->getDate()),
            htmlspecialchars(number_format($this->transaction->getAmount(), 2)),
            htmlspecialchars($accountDetails['name']),
            htmlspecialchars($otherPartyDetails['name']),
            htmlspecialchars($this->transaction->getMemo())
        );
    }

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    public function renderActions(): string
    {
        $html = '<div class="actions">';
        foreach ($this->buttons as $button) {
            $html .= $this->renderButton($button);
        }
        $html .= '</div>';
        return $html;
    }

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    public function addButton(string $type, array $params): void
    {
        $this->buttons[] = [
            'type' => $type,
            'params' => $params
        ];
    }

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    private function renderButton(array $button): string
    {
        $id = $button['params']['id'] ?? '';
        return sprintf(
            '<button name="%s[%s]" type="submit">%s</button>',
            htmlspecialchars($button['type']),
            htmlspecialchars($id),
            htmlspecialchars($this->getButtonLabel($button['type']))
        );
    }

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    private function getButtonLabel(string $type): string
    {
        $labels = [
            'AddCustomer' => 'Add Customer',
            'AddVendor' => 'Add Vendor',
            'ProcessTransaction' => 'Process Transaction'
        ];
        return $labels[$type] ?? $type;
    }
}