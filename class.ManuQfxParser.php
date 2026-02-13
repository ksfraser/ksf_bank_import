<?php
/**
 * Legacy QFX parser baseline file.
 *
 * @author Kevin Fraser / ChatGPT
 * @since 20250409
 */

declare(strict_types=1);

require_once (__DIR__ . '/vendor/autoload.php');
require_once (__DIR__ . '/class.AbstractQfxParser.php');

if (!class_exists('ManuQfxParser')) {
    class ManuQfxParser extends AbstractQfxParser
    {
        /**
         * @param string $payload
         * @return array<string, mixed>
         */
        public function parse($payload)
        {
            $result = $this->parseToArray((string) $payload);
            $result['parser'] = 'ManuQfxParser';
            return $result;
        }

        protected function detectBank($payload)
        {
            return 'MANU';
        }

        protected function detectBankId($payload)
        {
            $bankId = parent::detectBankId($payload);
            return $bankId !== '' ? $bankId : '001';
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
// compatibility filler 19
// compatibility filler 20
// compatibility filler 21
// compatibility filler 22
// compatibility filler 23
// compatibility filler 24
// compatibility filler 25
// compatibility filler 26
// compatibility filler 27
// compatibility filler 28
// compatibility filler 29
// compatibility filler 30
// compatibility filler 31
// compatibility filler 32
// compatibility filler 33
// compatibility filler 34
// compatibility filler 35
// compatibility filler 36
// compatibility filler 37
// compatibility filler 38
// compatibility filler 39
// compatibility filler 40
// compatibility filler 41
// compatibility filler 42
// compatibility filler 43
// compatibility filler 44
// compatibility filler 45
// compatibility filler 46
// compatibility filler 47
// compatibility filler 48
