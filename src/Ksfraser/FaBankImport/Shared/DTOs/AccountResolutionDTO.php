<?php
namespace Ksfraser\FaBankImport\Shared\DTOs;

class AccountResolutionDTO {
    public $detectedAccounts;
    public $faAccounts;
    public $mappings;
    
    public function __construct($detectedAccounts, $faAccounts, $mappings = []) {
        $this->detectedAccounts = $detectedAccounts;
        $this->faAccounts = $faAccounts;
        $this->mappings = $mappings;
    }
}
