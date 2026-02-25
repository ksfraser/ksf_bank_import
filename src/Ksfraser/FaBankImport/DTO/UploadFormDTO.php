<?php
namespace Ksfraser\FaBankImport\DTO;

class UploadFormDTO {
    public $parsers;
    public $selectedParser;
    public function __construct($parsers, $selectedParser = null) {
        $this->parsers = $parsers;
        $this->selectedParser = $selectedParser;
    }
}
