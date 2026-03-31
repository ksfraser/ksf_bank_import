<?php
namespace Ksfraser\FaBankImport\Shared\DTOs;

class ImportSummaryDTO {
    public $results;
    
    public function __construct($results) {
        $this->results = $results;
    }
}
