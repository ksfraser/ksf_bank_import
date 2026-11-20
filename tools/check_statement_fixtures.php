<?php

declare(strict_types=1);

/**
 * Guard against committing un-scrubbed bank statement data.
 *
 * Raw statement exports (CSV / OFX / QFX / QBO) carry card numbers, account
 * numbers, IBANs, e-mail addresses and phone numbers. Those files are only
 * allowed in the tree after passing through
 * tools/sanitize_statement_data.php --write, and every reviewed fixture is
 * pinned by SHA-256 in tools/fixtures.sha256 so a re-sanitized replacement
 * cannot slip in unreviewed.
 *
 * This tool is deliberately NOT the sanitizer: re-running the sanitizer is not
 * idempotent, so "sanitize and compare" can never be a gate. It looks for
 * identity leaks instead, and verifies the reviewed-fixture manifest.
 *
 * Usage:
 *   php tools/check_statement_fixtures.php            # leak scan + manifest check
 *   php tools/check_statement_fixtures.php --write-manifest
 *   php tools/check_statement_fixtures.php --scan     # leak scan only
 */

/**
 * Statement file extensions this gate covers.
 *
 * @return array<int,string>
 */
function sfx_extensions()
{
    return array('csv', 'ofx', 'qfx', 'qbo');
}

/**
 * Tracked paths that are statement-shaped but are not statement data, and
 * upstream vendor fixtures that are not ours to scrub.
 *
 * @return array<int,string>
 */
function sfx_skip_prefixes()
{
    return array(
        'docs/REQUIREMENTS_TRACEABILITY_MATRIX.csv',
        'includes/vendor/',
        'vendor/',
    );
}

/**
 * Luhn check over a digit string.
 *
 * @param string $digits Digits only.
 * @return bool
 */
function sfx_luhn($digits)
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
 * IBAN check (mod-97 == 1).
 *
 * @param string $iban Candidate without spaces.
 * @return bool
 */
function sfx_iban_valid($iban)
{
    if (!preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{10,30}$/', $iban)) {
        return false;
    }

    $rearranged = substr($iban, 4) . substr($iban, 0, 4);
    $numeric = '';
    for ($i = 0; $i < strlen($rearranged); $i++) {
        $char = $rearranged[$i];
        $numeric .= ctype_alpha($char) ? (string) (ord($char) - 55) : $char;
    }

    $remainder = 0;
    for ($i = 0; $i < strlen($numeric); $i++) {
        $remainder = ($remainder * 10 + (int) $numeric[$i]) % 97;
    }

    return $remainder === 1;
}

/**
 * Find identity leaks in one file's text.
 *
 * @param string $text File contents.
 * @return array<int,string> Human-readable findings.
 */
function sfx_scan_text($text)
{
    $findings = array();
    $lines = preg_split('/\r\n|\r|\n/', (string) $text);

    foreach ($lines as $number => $line) {
        $lineNo = $number + 1;

        // Payment card numbers. Only shapes a card actually uses, and only
        // Luhn-valid values whose issuer prefix is a card range: FITIDs,
        // timestamps and 23-digit bank references otherwise trip the check
        // roughly one time in ten.
        $groups = array(
            '/(?<![0-9])((?:\d{4}[ -]){3}\d{4})(?![0-9])/',
            '/(?<![0-9])([3-6]\d{12,18})(?![0-9])/',
        );
        $ofxDateLine = (bool) preg_match('/<(FITID|DTPOSTED|DTSERVER|TRNUID|DTASOF|BANKID|REFNUM)>/i', $line);
        $ofxAccountLine = (bool) preg_match('/<(ACCTID|CARDNUM)>/i', $line);
        foreach ($groups as $index => $pattern) {
            if ($index === 1 && $ofxDateLine && !$ofxAccountLine) {
                continue;
            }
            if (!preg_match_all($pattern, $line, $matches)) {
                continue;
            }
            foreach ($matches[1] as $candidate) {
                $digits = preg_replace('/[^0-9]/', '', $candidate);
                if (sfx_luhn($digits)) {
                    $findings[] = "line {$lineNo}: payment card number ({$digits})";
                }
            }
        }

        // IBANs that actually validate (fake scrubber output does not).
        if (preg_match_all('/\b([A-Z]{2}\d{2}[A-Z0-9]{10,30})\b/', $line, $matches)) {
            foreach ($matches[1] as $candidate) {
                if (sfx_iban_valid($candidate)) {
                    $findings[] = "line {$lineNo}: valid IBAN ({$candidate})";
                }
            }
        }

        // E-mail addresses, except documentation placeholders.
        if (preg_match_all('/[\w.+-]+@[\w-]+\.[A-Za-z]{2,}/', $line, $matches)) {
            foreach ($matches[0] as $email) {
                if (preg_match('/@(?:example\.(?:com|org|net)|localhost)$/i', $email)) {
                    continue;
                }
                $findings[] = "line {$lineNo}: e-mail address ({$email})";
            }
        }

        // North American phone numbers (dates and amounts do not match).
        if (preg_match_all('/\b\(?\d{3}\)?[ .-]\d{3}[ .-]\d{4}\b/', $line, $matches)) {
            foreach ($matches[0] as $phone) {
                $findings[] = "line {$lineNo}: phone number ({$phone})";
            }
        }
    }

    return $findings;
}

/**
 * Tracked statement files, minus the documented skip list.
 *
 * @param string $root Repository root.
 * @return array<int,string> Repo-relative paths.
 */
function sfx_statement_files($root)
{
    $output = array();
    $command = 'git -C ' . escapeshellarg($root) . ' ls-files';
    exec($command, $output, $status);
    if ($status !== 0) {
        throw new RuntimeException('git ls-files failed; run this from a git checkout.');
    }

    $extensions = sfx_extensions();
    $skips = sfx_skip_prefixes();
    $files = array();
    foreach ($output as $path) {
        $path = trim($path);
        if ($path === '') {
            continue;
        }
        foreach ($skips as $prefix) {
            if (strpos($path, $prefix) === 0) {
                continue 2;
            }
        }
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($extension, $extensions, true)) {
            $files[] = $path;
        }
    }
    sort($files);

    return $files;
}

/**
 * Read the reviewed-fixture manifest into path => sha256.
 *
 * @param string $manifest Absolute path.
 * @return array<string,string>
 */
function sfx_read_manifest($manifest)
{
    if (!is_file($manifest)) {
        return array();
    }

    $entries = array();
    foreach (file($manifest, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos($line, '#') === 0) {
            continue;
        }
        if (!preg_match('/^([0-9a-f]{64})\s{2}(.+)$/', $line, $m)) {
            continue;
        }
        $entries[$m[2]] = $m[1];
    }

    return $entries;
}

/**
 * Verify every tracked statement file is reviewed and unchanged.
 *
 * @param string              $root     Repository root.
 * @param array<string,string> $manifest Manifest entries.
 * @return array<int,string> Problems found.
 */
function sfx_verify_manifest($root, array $manifest)
{
    $problems = array();

    foreach ($manifest as $path => $hash) {
        $absolute = $root . '/' . $path;
        if (!is_file($absolute)) {
            $problems[] = "manifest lists missing file: {$path}";
            continue;
        }
        $actual = hash_file('sha256', $absolute);
        if ($actual !== $hash) {
            $problems[] = "changed since review (re-sanitize/review, then --write-manifest): {$path}";
        }
    }

    foreach (sfx_statement_files($root) as $path) {
        if (!isset($manifest[$path])) {
            $problems[] = "not in tools/fixtures.sha256 (sanitize + review, then --write-manifest): {$path}";
        }
    }

    return $problems;
}

/**
 * Regenerate the manifest for every tracked statement file.
 *
 * @param string $root     Repository root.
 * @param string $manifest Destination.
 * @return int Number of entries written.
 */
function sfx_write_manifest($root, $manifest)
{
    $lines = array(
        '# Reviewed statement fixtures. Regenerate only after sanitizing and',
        '# eyeballing the diff: php tools/check_statement_fixtures.php --write-manifest',
    );

    $files = sfx_statement_files($root);
    foreach ($files as $path) {
        $lines[] = hash_file('sha256', $root . '/' . $path) . '  ' . $path;
    }

    file_put_contents($manifest, implode("\n", $lines) . "\n");

    return count($files);
}

/**
 * Run the gate.
 *
 * @param string   $root     Repository root.
 * @param string   $manifest Manifest path.
 * @param bool     $scanOnly Skip the manifest check.
 * @param resource $out      Output stream.
 * @return int Exit code.
 */
function sfx_main($root, $manifest, $scanOnly, $out)
{
    $failed = false;

    foreach (sfx_statement_files($root) as $path) {
        $findings = sfx_scan_text((string) file_get_contents($root . '/' . $path));
        foreach ($findings as $finding) {
            fwrite($out, "LEAK  {$path}: {$finding}\n");
            $failed = true;
        }
    }

    if (!$scanOnly) {
        foreach (sfx_verify_manifest($root, sfx_read_manifest($manifest)) as $problem) {
            fwrite($out, "MANIFEST  {$problem}\n");
            $failed = true;
        }
    }

    if ($failed) {
        fwrite($out, "\nStatement fixture gate failed.\n");
        return 1;
    }

    fwrite($out, "Statement fixture gate passed.\n");

    return 0;
}

if (isset($argv[0]) && realpath($argv[0]) === realpath(__FILE__)) {
    $root = dirname(__DIR__);
    $manifestPath = __DIR__ . '/fixtures.sha256';

    if (in_array('--write-manifest', $argv, true)) {
        $count = sfx_write_manifest($root, $manifestPath);
        fwrite(STDOUT, "Wrote {$count} entries to tools/fixtures.sha256\n");
        exit(0);
    }

    exit(sfx_main($root, $manifestPath, in_array('--scan', $argv, true), STDOUT));
}
