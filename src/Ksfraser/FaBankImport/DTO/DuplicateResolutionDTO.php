<?php
namespace Ksfraser\FaBankImport\DTO;

class DuplicateResolutionDTO {
    public $duplicates;
    public function __construct($duplicates) {
        $this->duplicates = $duplicates;
    }
}
