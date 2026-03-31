<?php
namespace Ksfraser\FaBankImport\Shared\DTOs;

class DuplicateResolutionDTO {
    public $duplicates;
    
    public function __construct($duplicates) {
        $this->duplicates = $duplicates;
    }
}
