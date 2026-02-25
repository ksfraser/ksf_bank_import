<?php
// UploadFormDTO: Data Transfer Object for upload form
class UploadFormDTO {
    public $parsers;
    public $selectedParser;
    public $selectedBankAccount;
    public function __construct($parsers, $selectedParser = null, $selectedBankAccount = null) {
        $this->parsers = $parsers;
        $this->selectedParser = $selectedParser;
        $this->selectedBankAccount = $selectedBankAccount;
    }
}

// ParseUploadedFilesDTO: Data Transfer Object for parse uploaded files
class ParseUploadedFilesDTO {
    public $statements;
    public $smt_ok;
    public $smt_err;
    public $trz_ok;
    public function __construct($statements, $smt_ok, $smt_err, $trz_ok) {
        $this->statements = $statements;
        $this->smt_ok = $smt_ok;
        $this->smt_err = $smt_err;
        $this->trz_ok = $trz_ok;
    }
}

// ImportSummaryDTO: Data Transfer Object for import summary
class ImportSummaryDTO {
    public $importResults;
    public function __construct($importResults) {
        $this->importResults = $importResults;
    }
}
