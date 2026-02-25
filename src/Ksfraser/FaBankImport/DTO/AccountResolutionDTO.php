<?php
namespace Ksfraser\FaBankImport\DTO;

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
