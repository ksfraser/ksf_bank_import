<?php
namespace Ksfraser\FaBankImport\Shared\DTOs;

class MappingConfirmationDTO {
    public $pendingMappings;
    
    public function __construct($pendingMappings) {
        $this->pendingMappings = $pendingMappings;
    }
}
