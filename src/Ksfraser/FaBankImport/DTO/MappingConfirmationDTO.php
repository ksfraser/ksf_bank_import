<?php
namespace Ksfraser\FaBankImport\DTO;

class MappingConfirmationDTO {
    public $pendingMappings;
    public function __construct($pendingMappings) {
        $this->pendingMappings = $pendingMappings;
    }
}
