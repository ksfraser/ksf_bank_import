<?php

/**
 * @author Kevin Fraser / ChatGPT
 * @since 20250409
 */

//we need to interpret the file and generate a new statement for each day of transactions

//TODO
//	Have a config whether we should match MEMO field i.e. INTERNET TRASFER/PAY/DEPOSIT/...
//	Have a further config to indicate chunk delimiters.  CIBC uses ";".  Is this standard?

require_once (__DIR__ . '/vendor/autoload.php' );
include_once( 'includes.inc' );

/************************************************
* Abstract class to parse a QFX/OFX file
************************************************/
abstract class AbstractQfxParser {
    protected $bank_from_file;
    protected $bankid_from_file;

    abstract public function parse($content, $static_data = array(), $debug = true);

    protected function detectBank($institute, $static_data) {
        if (null !== $institute->name) {
            $this->bank_from_file = true;
            return (string) $institute->name;
        } elseif (isset($static_data['account_name'])) {
            $this->bank_from_file = false;
            return $static_data['account_name'];
        } else {
            $this->bank_from_file = false;
            return "Savings";
        }
    }

    protected function detectBankId($institute, $static_data) {
        if (null !== $institute->id) {
            $this->bankid_from_file = true;
            return (string) $institute->id;
        } elseif (isset($static_data['account_code'])) {
            $this->bankid_from_file = false;
            return $static_data['account_code'];
        } else {
            $this->bankid_from_file = false;
            return '1060';
        }
    }
}

class CibcQfxParser extends AbstractQfxParser {
    public function parse($content, $static_data = array(), $debug = true) {
        // Implement CIBC-specific parsing logic
        // Example: Handle CIBC-specific transaction types or formats
    }
}

class PmcQfxParser extends AbstractQfxParser {
    public function parse($content, $static_data = array(), $debug = true) {
        // Implement PMC-specific parsing logic
        // Example: Handle PMC-specific transaction types or formats
    }
}

class ManuQfxParser extends AbstractQfxParser {
    public function parse($content, $static_data = array(), $debug = true) {
        // Implement MANU-specific parsing logic
        // Example: Handle MANU-specific transaction types or formats
    }
}

class QfxParserFactory {
    public static function createParser($content) {
        // Detection logic to determine which parser to use
        if (strpos($content, 'CIBC') !== false) {
            return new CibcQfxParser();
        } elseif (strpos($content, 'PMC') !== false) {
            return new PmcQfxParser();
        } elseif (strpos($content, 'MANU') !== false) {
            return new ManuQfxParser();
        } else {
            throw new Exception("Unknown bank type in QFX content");
        }
    }
}



