#!/usr/bin/env php
<?php

/**
 * Sanitize bank statement test data (CSV / QFX / OFX) before it is committed.
 *
 * Keeps everything a parser test needs — dates, amounts, direction, currency,
 * activity/status codes, merchant category and the header/column layout — and
 * deterministically replaces the identifying content: card and account
 * numbers, reference numbers, merchant names, cities, provinces, postal codes,
 * name-on-card/holder names and free-text memos.
 *
 * Deterministic: the same input always produces the same output, and the same
 * value maps to the same replacement within a run, so fixtures stay stable in
 * diffs and tests can still match on names. It is a one-way scrub, not an
 * idempotent transform: running it again over an already-sanitized file would
 * re-map those values, so run it once, on the raw export.
 *
 * Usage:
 *   php tools/sanitize_statement_data.php [--write] [--check] [--mask-pans] file...
 *
 *   --write      Rewrite the files in place (default: print what would change).
 *   --check      Exit non-zero if any file still has data that would change.
 *   --mask-pans  Only mask leftover Luhn-valid card numbers (safe to re-run).
 *
 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
 */

declare(strict_types=1);

/**
 * Select a stable replacement from a list.
 *
 * @param array<int,string> $choices Replacement pool.
 * @param string            $seed    Original value.
 * @return string
 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
 */
function sd_pick(array $choices, $seed)
{
    $index = hexdec(substr(md5((string) $seed), 0, 4)) % count($choices);
    return $choices[$index];
}

/**
 * Replace every digit while keeping the surrounding punctuation.
 *
 * Keeps masks ("************2251"), separators ("543-997******6030") and
 * reference lengths intact so parsers see the same shape.
 *
 * @param string $value Original value.
 * @return string
 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
 */
function sd_digits($value)
{
    $digits = md5('digits:' . $value);
    $out = '';
    $i = 0;
    $length = strlen($value);

    for ($p = 0; $p < $length; $p++) {
        $char = $value[$p];
        if ($char >= '0' && $char <= '9') {
            $out .= (string) (hexdec($digits[$i % 32]) % 10);
            $i++;
        } else {
            $out .= $char;
        }
    }

    return $out;
}

/**
 * Replace the identifying characters of an account/IBAN/card id: digits are
 * regenerated deterministically, letters become X, punctuation is preserved.
 *
 * @param string $value Original value.
 * @return string
 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
 */
function sd_account($value)
{
    $out = '';
    $length = strlen($value);
    for ($p = 0; $p < $length; $p++) {
        $char = $value[$p];
        if (($char >= 'A' && $char <= 'Z') || ($char >= 'a' && $char <= 'z')) {
            $out .= 'X';
        } else {
            $out .= $char;
        }
    }

    return sd_digits($out);
}

/**
 * Canonical value types the CSV sanitizer understands.
 *
 * @return array<string,string> Normalized header => type.
 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
 */
function sd_csv_column_types()
{
    return array(
        'name on card' => 'person',
        'cardholder name' => 'person',
        'card holder name' => 'person',
        'card holder' => 'person',

        'merchant name' => 'merchant',
        'merchant' => 'merchant',
        'description' => 'merchant',
        'details' => 'merchant',
        'payee' => 'merchant',
        'narrative' => 'merchant',
        'transaction details' => 'merchant',

        'card number' => 'card',
        'transaction card number' => 'card',
        'card no' => 'card',

        'merchant city' => 'city',
        'city' => 'city',
        'town' => 'city',

        'merchant state/province' => 'province',
        'merchant state or province' => 'province',
        'merchant state' => 'province',
        'merchant province' => 'province',
        'province' => 'province',
        'state' => 'province',

        'merchant postal code' => 'postal',
        'merchant postal code/zip' => 'postal',
        'merchant postal code or zip' => 'postal',
        'merchant zip' => 'postal',
        'merchant zip/postal code' => 'postal',
        'postal code' => 'postal',
        'postal code/zip' => 'postal',
        'zip' => 'postal',

        'reference number' => 'reference',
        'reference' => 'reference',
        'ref number' => 'reference',
        'reference no' => 'reference',
        'transaction id' => 'reference',
        'referinta oper. document' => 'reference',

        'account' => 'account',
        'account number' => 'account',

        'account type' => null,
        'account number' => 'account',
        'transaction date' => null,
        'cheque number' => 'reference',
        'check number' => 'reference',
        'description 1' => 'merchant',
        'description 2' => 'merchant',
        'cad$' => null,
        'usd$' => null,

        'titular cont' => 'holder',
        'contul pentru care s-a generat extrasul' => 'account',
        'tranzactii finalizate (detalii)' => 'merchant',
    );
}

/**
 * Normalize a CSV header cell for lookup.
 *
 * @param string $cell Raw cell.
 * @return string
 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
 */
function sd_normalize_header($cell)
{
    $cell = preg_replace('/^\xEF\xBB\xBF/', '', (string) $cell);
    $cell = trim($cell, " \t\n\r\0\x0B\"'");
    $cell = preg_replace('/\s+/', ' ', $cell);
    return strtolower($cell);
}

/**
 * Sanitize one value of a known type.
 *
 * @param string $type  Canonical type.
 * @param string $value Original value.
 * @return string
 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
 */
function sd_sanitize_value($type, $value)
{
    if ($value === '') {
        return '';
    }

    switch ($type) {
        case 'person':
            return sd_pick(array('SAMPLE USER', 'JANE SAMPLE', 'ALEX SAMPLE'), $value);
        case 'merchant':
            return sd_pick(array(
                'CORNER STORE',
                'SAMPLE MARKET',
                'COFFEE SHOP',
                'SAMPLE MERCHANT',
                'HARDWARE STORE',
            ), $value);
        case 'city':
            return sd_pick(array('SAMPLE CITY', 'SAMPLE TOWN', 'SAMPLE VILLAGE'), $value);
        case 'province':
            return sd_pick(array('AB', 'ON', 'ZZ'), $value);
        case 'postal':
            return sd_pick(array('X0X 0X0', 'X1X1X1', 'X2X 2X2'), $value);
        case 'holder':
            return 'SAMPLE HOLDING LTD';
        case 'label':
            return sd_digits($value);
        case 'account':
            return sd_account($value);
        case 'card':
        case 'reference':
            return sd_digits($value);
    }

    return $value;
}

/**
 * Sanitize a CSV statement file.
 *
 * @param string $content File contents.
 * @return string
 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
 */
function sd_sanitize_csv($content)
{
    $lines = preg_split('/\r\n|\r|\n/', $content);
    $headerLine = array_shift($lines);

    if ($headerLine === null || trim($headerLine) === '') {
        return $content;
    }

    $delimiter = substr_count($headerLine, "\t") > substr_count($headerLine, ',') ? "\t" : ',';

    $types = sd_csv_column_types();
    $headers = str_getcsv($headerLine, $delimiter);
    $columnTypes = array();
    foreach ($headers as $index => $cell) {
        $normalized = sd_normalize_header($cell);
        $columnTypes[$index] = isset($types[$normalized]) ? $types[$normalized] : null;
    }

    $headerless = false;
    if (!in_array('reference', $columnTypes, true) && !in_array('merchant', $columnTypes, true)) {
        // Headerless banks (Manulife): account label, date, amount, description.
        if (preg_match('/account/i', (string) $headers[0])
            && isset($headers[1]) && preg_match('#^\d{1,2}/\d{1,2}/\d{4}$#', trim((string) $headers[1]))) {
            $headerless = true;
            $columnTypes = array(0 => 'label', 1 => null, 2 => null, 3 => 'merchant');
            $lines = array_merge(array($headerLine), $lines);
            $headers = array('Account', 'Date', 'Amount', 'Description');
        }
    }

    $output = array();
    if (!$headerless) {
        $output[] = $headerLine;
    }

    foreach ($lines as $line) {
        if (trim($line) === '') {
            $output[] = $line;
            continue;
        }

        $cells = str_getcsv($line, $delimiter);
        $cells = array_pad($cells, count($headers), '');
        foreach ($cells as $index => $value) {
            if (isset($columnTypes[$index]) && $columnTypes[$index] !== null) {
                $cells[$index] = sd_sanitize_value($columnTypes[$index], $value);
            }
        }

        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, array_slice($cells, 0, count($headers)), $delimiter, '"', '');
        rewind($stream);
        $output[] = rtrim((string) stream_get_contents($stream), "\n");
        fclose($stream);
    }

    return sd_mask_pans(implode("\n", $output) . (substr($content, -1) === "\n" ? "\n" : ''));
}

/**
 * Sanitize a QFX/OFX statement file (SGML or XML flavour).
 *
 * @param string $content File contents.
 * @return string
 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
 */
function sd_sanitize_markup($content)
{
    $replacements = array(
        'NAME' => 'merchant',
        'MEMO' => 'merchant',
        'PAYEE' => 'merchant',
        'DESC' => 'merchant',
        'ORG' => 'company',
        'ADDR1' => 'address',
        'ADDR2' => 'address',
        'ADDR3' => 'address',
        'CITY' => 'city',
        'STATE' => 'province',
        'POSTALCODE' => 'postal',
        'PHONE' => 'phone',
        'EMAIL' => 'email',
        'ACCTID' => 'account',
        'BANKID' => 'account',
        'BRANCHID' => 'account',
        'USERID' => 'account',
        'CARDNUM' => 'account',
    );

    return sd_mask_pans(preg_replace_callback(
        '#(<(?:' . implode('|', array_keys($replacements)) . ')>)([^<\r\n]*)#i',
        function ($m) use ($replacements) {
            $tag = strtoupper(substr($m[1], 1, -1));
            $type = isset($replacements[$tag]) ? $replacements[$tag] : null;
            $value = $m[2];

            if ($type === null || trim($value) === '') {
                return $m[0];
            }

            switch ($type) {
                case 'address':
                    return $m[1] . '123 SAMPLE ST';
                case 'phone':
                    return $m[1] . '555-0100';
                case 'email':
                    return $m[1] . 'sample@example.com';
                case 'company':
                    return $m[1] . sd_pick(array('SAMPLE HOLDING LTD', 'SAMPLE COMPANY INC'), $value);
            }

            return $m[1] . sd_sanitize_value($type, trim($value));
        },
        $content
    ));
}

/**
 * Sanitize one file according to its extension.
 *
 * @param string $path File path.
 * @return string Sanitized contents.
 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
 */
/**
 * Luhn check over a digit string.
 *
 * @param string $digits Digits only.
 * @return bool
 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
 */
function sd_luhn($digits)
{
    if (!ctype_digit($digits) || strlen($digits) < 12) {
        return false;
    }

    $sum = 0;
    $alternate = false;
    for ($i = strlen($digits) - 1; $i >= 0; $i--) {
        $value = (int) $digits[$i];
        if ($alternate) {
            $value *= 2;
            if ($value > 9) {
                $value -= 9;
            }
        }
        $sum += $value;
        $alternate = !$alternate;
    }

    return $sum % 10 === 0;
}

/**
 * Mask any payment-card number left in free text or a kept reference column.
 *
 * The column map cannot reach a card number that a bank wrote into a
 * description ("Ordin de plata ... 5106...") or an OFX element the parser
 * already whitelisted, so a final Luhn-guarded pass runs over the whole file.
 *
 * @param string $text Whole (already sanitized) file contents.
 * @return string
 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
 */
function sd_mask_pans($text)
{
    $mask = function ($candidate) {
        $digits = preg_replace('/[^0-9]/', '', $candidate);
        if (!sd_luhn($digits) || !in_array($digits[0], array('3', '4', '5', '6'), true)) {
            return $candidate;
        }

        return str_repeat('*', strlen($digits) - 4) . substr($digits, -4);
    };

    $text = preg_replace_callback('/(?<!\d)([3-6]\d{12,18})(?!\d)/', function ($m) use ($mask) {
        return $mask($m[1]);
    }, $text);

    return preg_replace_callback('/(?<!\d)(?:\d{4}[ -]){3}\d{4}(?!\d)/', function ($m) use ($mask) {
        return $mask($m[0]);
    }, $text);
}

function sd_sanitize_file($path)
{
    $content = (string) file_get_contents($path);
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    return $extension === 'csv'
        ? sd_sanitize_csv($content)
        : sd_sanitize_markup($content);
}

/**
 * CLI entry point.
 *
 * @param array<int,string> $arguments Arguments after the script name.
 * @return int Exit code.
 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
 */
function sd_main(array $arguments)
{
    $options = array('write' => false, 'check' => false, 'mask_pans' => false);
    $files = array();

    foreach ($arguments as $argument) {
        if ($argument === '--write') {
            $options['write'] = true;
        } elseif ($argument === '--check') {
            $options['check'] = true;
        } elseif ($argument === '--mask-pans') {
            $options['mask_pans'] = true;
        } else {
            $files[] = $argument;
        }
    }

    if (!$files) {
        fwrite(STDERR, "usage: php tools/sanitize_statement_data.php [--write|--check] [--mask-pans] file...\n");
        return 2;
    }

    $dirty = 0;

    foreach ($files as $file) {
        if (!is_file($file)) {
            fwrite(STDERR, "not a file: $file\n");
            return 2;
        }

        $original = (string) file_get_contents($file);
        $sanitized = $options['mask_pans']
            ? sd_mask_pans($original)
            : sd_sanitize_file($file);

        if ($sanitized === $original) {
            echo "clean   $file\n";
            continue;
        }

        if ($options['write']) {
            file_put_contents($file, $sanitized);
            echo "wrote   $file\n";
        } else {
            echo "dirty   $file\n";
        }

        if ($options['check']) {
            $dirty++;
        }
    }

    return ($options['check'] && $dirty > 0) ? 1 : 0;
}

if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === realpath(__FILE__)) {
    exit(sd_main(array_slice($argv, 1)));
}
