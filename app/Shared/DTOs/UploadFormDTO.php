<?php
namespace Ksfraser\FaBankImport\Shared\DTOs;

/**
 * Encapsulates upload form state for parser selection and bank account configuration
 */
class UploadFormDTO
{
    public $parsers; // array of parser options
    public $selectedParser; // string, currently selected parser
    public $selectedBankAccount; // string, currently selected bank account

    public function __construct(array $data = [])
    {
        $this->parsers = $data['parsers'] ?? [];
        $this->selectedParser = $data['selectedParser'] ?? null;
        $this->selectedBankAccount = $data['selectedBankAccount'] ?? null;
    }

    /**
     * Validate the form state
     */
    public function isValid(): bool
    {
        return !empty($this->selectedParser) && !empty($this->selectedBankAccount);
    }
}
