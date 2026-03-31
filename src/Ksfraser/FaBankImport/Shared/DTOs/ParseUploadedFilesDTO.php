<?php
namespace Ksfraser\FaBankImport\Shared\DTOs;

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
