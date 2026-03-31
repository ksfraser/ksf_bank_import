<?php
namespace Ksfraser\FaBankImport\Shared\DTOs;

class ParseFilesDTO {
    public $statements;
    public $validCount;
    public $invalidCount;
    public $transactionCount;
    
    public function __construct($statements, $validCount, $invalidCount, $transactionCount) {
        $this->statements = $statements;
        $this->validCount = $validCount;
        $this->invalidCount = $invalidCount;
        $this->transactionCount = $transactionCount;
    }
}
