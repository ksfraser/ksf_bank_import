<?php
namespace Ksfraser\FaBankImport\Views;

class BankAccountDropdownView
{
    private $accounts = [];
    private $selectedAccount = '';
    private $name = 'bank_account';

    public function __construct(array $accounts, $selectedAccount = '', $name = 'bank_account')
    {
        $this->accounts = $accounts;
        $this->selectedAccount = $selectedAccount;
        $this->name = $name;
    }

    public function getHtml(): string
    {
        $html = "<select name='" . htmlspecialchars($this->name) . "'>";
        foreach ($this->accounts as $id => $label) {
            $selected = ($id == $this->selectedAccount) ? 'selected' : '';
            $html .= "<option value='" . htmlspecialchars($id) . "' $selected>" . htmlspecialchars($label) . "</option>";
        }
        $html .= "</select>";
        return $html;
    }

    public function toHtml(): void
    {
        echo $this->getHtml();
    }

    public function render(array $accounts = null, $selectedAccount = null, $name = null): void
    {
        if ($accounts !== null) $this->accounts = $accounts;
        if ($selectedAccount !== null) $this->selectedAccount = $selectedAccount;
        if ($name !== null) $this->name = $name;
        $this->toHtml();
    }
}
