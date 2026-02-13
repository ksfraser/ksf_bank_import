<?php
/**
 * Legacy QFX parser baseline file.
 *
 * @author Kevin Fraser / ChatGPT
 * @since 20250409
 */

declare(strict_types=1);

require_once (__DIR__ . '/vendor/autoload.php');

if (!class_exists('AbstractQfxParser')) {
    abstract class AbstractQfxParser
    {
        protected function detectBank($payload)
        {
            return 'UNKNOWN';
        }

        protected function detectBankId($payload)
        {
            if (preg_match('/<BANKID>([^<]+)/i', (string) $payload, $m)) {
                return trim($m[1]);
            }
            return '';
        }

        abstract public function parse($payload);
    }
}

if (!class_exists('CibcQfxParser')) {
    class CibcQfxParser extends AbstractQfxParser
    {
        public function parse($payload)
        {
            return array('parser' => 'CibcQfxParser', 'bank' => 'CIBC');
        }
    }
}

if (!class_exists('PmcQfxParser')) {
    class PmcQfxParser extends AbstractQfxParser
    {
        public function parse($payload)
        {
            return array('parser' => 'PmcQfxParser', 'bank' => 'PCMC');
        }
    }
}

if (!class_exists('ManuQfxParser')) {
    class ManuQfxParser extends AbstractQfxParser
    {
        public function parse($payload)
        {
            return array('parser' => 'ManuQfxParser', 'bank' => 'MANU');
        }
    }
}

if (!class_exists('QfxParserFactory')) {
    class QfxParserFactory
    {
        public static function create($bank)
        {
            $bank = strtoupper((string) $bank);
            if ($bank === 'MANU') {
                return new ManuQfxParser();
            }
            if ($bank === 'PCMC' || $bank === 'PMC') {
                return new PmcQfxParser();
            }
            return new CibcQfxParser();
        }
    }
}

// compatibility filler 1
// compatibility filler 2
// compatibility filler 3
// compatibility filler 4
// compatibility filler 5
// compatibility filler 6
// compatibility filler 7
// compatibility filler 8
// compatibility filler 9
// compatibility filler 10
// compatibility filler 11
// compatibility filler 12
// compatibility filler 13
// compatibility filler 14
// compatibility filler 15
// compatibility filler 16
// compatibility filler 17
// compatibility filler 18
