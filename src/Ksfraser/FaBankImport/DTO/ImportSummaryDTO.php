<?php
namespace Ksfraser\FaBankImport\DTO;

class ImportSummaryDTO {
    public $results;
    public function __construct($results) {
        $this->results = $results;
    }
}
