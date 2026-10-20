<?php
// @BABOK Referenced in RTM for domain layer (BR-001 through BR-012, ARCH-001 through ARCH-010)
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\FaBankImport\Interfaces\TransactionViewInterface;
use Ksfraser\FaBankImport\Interfaces\BankTransactionInterface;

/**
 * @BABOK RTM: FR-009 / IR-001 / ARCH-008; file: Views/HtmlTransactionView.php; coverage: docs/TestCases/ (file rollup)
 */
class HtmlTransactionView implements TransactionViewInterface
{
    private $transaction;
    private $buttons = [];

    /** @BABOK FR-009 / IR-001 / ARCH-008 — method: __construct */
    public function __construct(BankTransactionInterface $transaction)
    {
        $this->transaction = $transaction;
    }

    /** @BABOK FR-009 / IR-001 / ARCH-008 — method: render */
    public function render(): string
    {
        $html = '<div class="transaction">';
        $html .= $this->renderTransactionDetails();
        $html .= $this->renderActions();
        $html .= '</div>';
        return $html;
    }

    /** @BABOK FR-009 / IR-001 / ARCH-008 — method: renderTransactionDetails */
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

    /** @BABOK FR-009 / IR-001 / ARCH-008 — method: renderActions */
    public function renderActions(): string
    {
        $html = '<div class="actions">';
        foreach ($this->buttons as $button) {
            $html .= $this->renderButton($button);
        }
        $html .= '</div>';
        return $html;
    }

    /** @BABOK FR-009 / IR-001 / ARCH-008 — method: addButton */
    public function addButton(string $type, array $params): void
    {
        $this->buttons[] = [
            'type' => $type,
            'params' => $params
        ];
    }

    /** @BABOK FR-009 / IR-001 / ARCH-008 — method: renderButton */
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

    /** @BABOK FR-009 / IR-001 / ARCH-008 — method: getButtonLabel */
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