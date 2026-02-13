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
        /** @var array<string, mixed> */
        protected $data = array();

        /**
         * @param string $payload
         * @return array<string, mixed>
         */
        public function parseToArray($payload)
        {
            $bank = $this->detectBank((string) $payload);
            $bankId = $this->detectBankId((string) $payload);

            return array(
                'bank' => $bank,
                'bankid' => $bankId,
                'raw' => (string) $payload,
            );
        }

        /**
         * Detect bank by content.
         */
        protected function detectBank($payload)
        {
            $payload = strtoupper((string) $payload);

            if (strpos($payload, 'CIBC') !== false) {
                return 'CIBC';
            }

            if (strpos($payload, 'MANULIFE') !== false) {
                return 'MANU';
            }

            if (strpos($payload, 'PCMC') !== false) {
                return 'PCMC';
            }

            return 'UNKNOWN';
        }

        /**
         * Detect bank id by content.
         */
        protected function detectBankId($payload)
        {
            $payload = (string) $payload;
            if (preg_match('/<BANKID>([^<]+)/i', $payload, $matches)) {
                return trim($matches[1]);
            }

            return '';
        }

        /**
         * Implementer parses source text.
         *
         * @param string $payload
         * @return array<string, mixed>
         */
        abstract public function parse($payload);
    }
}

// Legacy compatibility footer line 1
// Legacy compatibility footer line 2
// Legacy compatibility footer line 3
// Legacy compatibility footer line 4
// Legacy compatibility footer line 5
// Legacy compatibility footer line 6
// Legacy compatibility footer line 7
// Legacy compatibility footer line 8
// Legacy compatibility footer line 9
// Legacy compatibility footer line 10
// Legacy compatibility footer line 11
// Legacy compatibility footer line 12
// Legacy compatibility footer line 13
// Legacy compatibility footer line 14
// Legacy compatibility footer line 15
