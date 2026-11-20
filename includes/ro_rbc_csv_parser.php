<?php

/**
 * RBC Royal Bank CSV statement parser.
 *
 * RBC exports a header row with both a CAD$ and a USD$ amount column; exactly
 * one is populated per transaction. Typical file:
 *
 *   Account Type,Account Number,Transaction Date,Cheque Number,
 *   Description 1,Description 2,CAD$,USD$
 *   Visa,4510144022764295,9/13/2026,,McDonalds 40016 AIRDRIE,,-5.24,
 *
 * The same file can arrive tab-separated when it was copied out of a mail
 * preview, so the delimiter is detected from the header rather than assumed.
 * Card charges are negative and payments/refunds positive, which is the
 * opposite sign convention to the WMMC export.
 *
 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
 */
class ro_rbc_csv_parser extends parser
{
    /**
     * Header spellings mapped to canonical keys.
     *
     * @return array<string,string>
     * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
     */
    protected function headerAliases()
    {
        return array(
            'account type' => 'accounttype',
            'account number' => 'account',
            'transaction date' => 'date',
            'date' => 'date',
            'cheque number' => 'chequenumber',
            'check number' => 'chequenumber',
            'cheque no' => 'chequenumber',
            'description 1' => 'description',
            'description' => 'description',
            'description 2' => 'description2',
            'cad$' => 'cad',
            'cad' => 'cad',
            'usd$' => 'usd',
            'usd' => 'usd',
        );
    }

    /**
     * Normalize a header cell.
     *
     * @param string $cell Raw header cell.
     * @return string
     * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
     */
    protected function normalizeHeaderCell($cell)
    {
        $cell = preg_replace('/^\xEF\xBB\xBF/', '', (string) $cell);
        $cell = trim($cell, " \t\n\r\0\x0B\"'");
        $cell = preg_replace('/\s+/', ' ', $cell);
        return strtolower($cell);
    }

    /**
     * Choose the delimiter from the busiest candidate in the header.
     *
     * @param string $header Header line.
     * @return string
     * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
     */
    protected function detectDelimiter($header)
    {
        $tabs = substr_count($header, "\t");
        $commas = substr_count($header, ',');

        return $tabs > $commas ? "\t" : ',';
    }

    /**
     * Normalize RBC's M/D/YYYY and MM/DD/YYYY dates.
     *
     * @param string $date Raw date cell.
     * @return string ISO date, or the original value when unrecognizable.
     * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
     */
    protected function normalizeDate($date)
    {
        $date = trim((string) $date);

        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $date, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[1], $m[2]);
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date)) {
            return $date;
        }

        return $date;
    }

    /**
     * Turn an amount cell into a signed float.
     *
     * Accepts "$1,234.56", "(10.00)", "10.00-", and an empty cell.
     *
     * @param string $amount Raw amount cell.
     * @return float|null Null when the cell is empty.
     * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
     */
    protected function parseAmount($amount)
    {
        $amount = trim((string) $amount);
        if ($amount === '') {
            return null;
        }

        $negative = false;
        if (strncmp($amount, '-', 1) === 0 || substr($amount, -1) === '-') {
            $negative = true;
        }
        if (strlen($amount) > 1 && $amount[0] === '(' && substr($amount, -1) === ')') {
            $negative = true;
        }

        $number = (float) preg_replace('/[^\d\.]/', '', $amount);

        return $negative ? -$number : $number;
    }

    /**
     * Pick the populated amount column and its currency.
     *
     * @param array<string,string> $row Parsed row.
     * @return array{0:float,1:string}|null Null when there is no amount.
     * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
     */
    protected function resolveAmount(array $row)
    {
        $cad = $this->parseAmount(isset($row['cad']) ? $row['cad'] : '');
        if ($cad !== null) {
            return array($cad, 'CAD');
        }

        $usd = $this->parseAmount(isset($row['usd']) ? $row['usd'] : '');
        if ($usd !== null) {
            return array($usd, 'USD');
        }

        return null;
    }

    /**
     * Import an RBC CSV export.
     *
     * @param string $content     File contents.
     * @param array  $static_data Form data (account, currency, bank_name).
     * @param bool   $debug       Echo parse commentary.
     * @return array<string,statement> Statements keyed by date.
     * @throws Exception When the file is empty or has no date column.
     * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
     */
    public function parse($content, $static_data = array(), $debug = true)
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $content);
        $headerLine = array_shift($lines);

        if ($headerLine === null || trim($headerLine) === '') {
            throw new Exception('Empty RBC CSV file.');
        }

        $delimiter = $this->detectDelimiter($headerLine);
        $aliases = $this->headerAliases();
        $columns = array();
        foreach (str_getcsv($headerLine, $delimiter) as $index => $cell) {
            $normalized = $this->normalizeHeaderCell($cell);
            $columns[$index] = isset($aliases[$normalized]) ? $aliases[$normalized] : $normalized;
        }

        if (!in_array('date', $columns, true)) {
            throw new Exception('RBC CSV column header mismatch: no transaction date column. Headers seen: '
                . implode(' | ', str_getcsv($headerLine, $delimiter)));
        }

        $statements = array();
        $accountsSeen = array();

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $cells = str_getcsv($line, $delimiter);
            $cells = array_slice(array_pad($cells, count($columns), ''), 0, count($columns));
            $row = array_combine($columns, array_map('strval', $cells));

            $date = $this->normalizeDate($this->column($row, 'date'));
            if ($date === '') {
                continue;
            }

            $resolved = $this->resolveAmount($row);
            if ($resolved === null) {
                continue;
            }
            list($signedAmount, $currency) = $resolved;

            $account = $this->column($row, 'account');
            $accountKey = preg_replace('/[^0-9A-Za-z]/', '', $account);
            $accountsSeen[$accountKey] = true;

            $description = trim($this->column($row, 'description'));
            $description2 = trim($this->column($row, 'description2'));
            if ($description2 !== '') {
                $description .= ' ' . $description2;
            }

            $key = $date . '|' . $accountKey . '|' . $currency;
            if (!isset($statements[$key])) {
                $stmt = new statement();
                $stmt->bank = isset($static_data['bank_name']) && $static_data['bank_name'] !== ''
                    ? $static_data['bank_name']
                    : 'RBC';
                $stmt->account = $account !== ''
                    ? $account
                    : (isset($static_data['account']) ? $static_data['account'] : 'RBC');
                $stmt->currency = $currency;
                $stmt->timestamp = $date;
                $stmt->startBalance = '0';
                $stmt->endBalance = '0';
                $stmt->sequence = '0';
                $stmt->number = '00000';
                $statements[$key] = $stmt;
            }

            $trz = new transaction();
            $trz->valueTimestamp = $date;
            $trz->entryTimestamp = $date;
            $trz->transactionAmount = abs($signedAmount);
            $trz->transactionType = 'TRF';

            // Charges are negative on an RBC card export; a positive row is a
            // payment when it says so, otherwise a refund/credit.
            if ($signedAmount < 0) {
                $trz->transactionDC = 'D';
            } elseif (preg_match('/payment|merci|thank you/i', $description)) {
                $trz->transactionDC = 'B';
            } else {
                $trz->transactionDC = 'C';
            }

            $accountType = $this->column($row, 'accounttype');
            if ($accountType !== '' && preg_match('/visa|master/i', $accountType)) {
                $trz->cardnumber = $account;
            }

            $trz->merchant = $description;
            $trz->transactionTitle1 = $description;
            $trz->transactionTitle = $description;
            $trz->memo = $description;
            $trz->category = '';

            $cheque = trim($this->column($row, 'chequenumber'));
            if ($cheque !== '') {
                if (ctype_digit($cheque)) {
                    $trz->checknumber = $cheque;
                }
                $trz->transactionCode = $cheque;
            } else {
                $trz->transactionCode = $date . '-' . substr(sha1($description . '|' . $signedAmount . '|' . $accountKey), 0, 10);
            }
            $trz->reference = $trz->transactionCode;

            $statements[$key]->addTransaction($trz);
        }

        // Give each statement its identity. A single-account export keeps the
        // historic "<date>-00000-0" id; several accounts on one date fold the
        // account's last four digits into the id, and a second statement for
        // the same date/account (e.g. a USD companion) takes sequence 1.
        $distinctAccounts = count($accountsSeen);
        $usedIds = array();
        $perDate = array();
        foreach ($statements as $stmt) {
            $perDate[$stmt->timestamp] = isset($perDate[$stmt->timestamp]) ? $perDate[$stmt->timestamp] + 1 : 1;
        }

        $final = array();
        foreach ($statements as $stmt) {
            $stmt->number = $distinctAccounts > 1
                ? substr(preg_replace('/\D/', '', $stmt->account), -4)
                : '00000';
            $sequence = 0;
            do {
                $stmt->sequence = (string) $sequence;
                $stmt->statementId = "{$stmt->timestamp}-{$stmt->number}-{$sequence}";
                $sequence++;
            } while (isset($usedIds[$stmt->statementId]));
            $usedIds[$stmt->statementId] = true;
            $final[$perDate[$stmt->timestamp] > 1 ? $stmt->statementId : $stmt->timestamp] = $stmt;
        }

        if ($debug) {
            echo 'RBC CSV: ' . count($final) . " statement(s)\n";
        }

        return $final;
    }

    /**
     * Read a canonical column, defaulting to ''.
     *
     * @param array<string,string> $row Parsed row.
     * @param string               $key Canonical key.
     * @return string
     * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
     */
    protected function column(array $row, $key)
    {
        return isset($row[$key]) ? (string) $row[$key] : '';
    }
}
