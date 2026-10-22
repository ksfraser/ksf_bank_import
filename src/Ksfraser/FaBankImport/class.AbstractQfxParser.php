<?php

/**
 * @author Kevin Fraser / ChatGPT
 * @since 20250409
 */

//we need to interpret the file and generate a new statement for each day of transactions

//TODO
//	Have a config whether we should match MEMO field i.e. INTERNET TRASFER/PAY/DEPOSIT/...
//	Have a further config to indicate chunk delimiters.  CIBC uses ";".  Is this standard?

require_once (__DIR__ . '/../../../vendor/autoload.php' );
if ( !defined( 'KSF_TEST_COMPAT' ) ) { include_once( 'includes.inc' ); }

/************************************************
* Abstract class to parse a QFX/OFX file
************************************************/
/**
 * @BABOK FR-IMP-005, FR-001 | BR-IMP-001 | UAT-IMP-001
 */
abstract class AbstractQfxParser {
    protected $bank_from_file;
    protected $bankid_from_file;

    /**
 * @BABOK FR-IMP-005, FR-001 | BR-IMP-001 | UAT-IMP-001
     */
    abstract public function parse($content, $static_data = array(), $debug = true);

    /**
 * @BABOK FR-IMP-005, FR-001 | BR-IMP-001 | UAT-IMP-001
     */
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

    /**
 * @BABOK FR-IMP-005, FR-001 | BR-IMP-001 | UAT-IMP-001
     */
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

/**
 * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
 */
class CibcQfxParser extends AbstractQfxParser {
    /**
 * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
     */
    public function parse($content, $static_data = array(), $debug = true) {
        // Implement CIBC-specific parsing logic
        // Example: Handle CIBC-specific transaction types or formats
    }
}

/**
 * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
 */
class PmcQfxParser extends AbstractQfxParser {
    /**
 * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
     */
    public function parse($content, $static_data = array(), $debug = true) {
        // Implement PMC-specific parsing logic
        // Example: Handle PMC-specific transaction types or formats
    }
}

/**
 * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
 */
class ManuQfxParser extends AbstractQfxParser {
    /**
 * @BABOK FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
     */
    public function parse($content, $static_data = array(), $debug = true) {
        // Implement MANU-specific parsing logic
        // Example: Handle MANU-specific transaction types or formats
    }
}

/**
 * @BABOK FR-IMP-002, FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
 */
class QfxParserFactory {
    /**
 * @BABOK FR-IMP-002, FR-IMP-005 | BR-IMP-001 | UAT-IMP-001
     */
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




