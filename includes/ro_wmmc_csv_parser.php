<?php

/**
 * WMMC (Walmart Canada credit card) CSV statement parser.
 *
 * The export format has changed several times over the years, so the parser
 * maps every documented header spelling onto one canonical set of keys and
 * treats the columns that only appear in newer exports as optional:
 *
 *   "Date","Activity Type","Merchant Name","Merchant Category","Amount","Rewards"
 *   "Transaction Date","Activity Type","Merchant Name","Merchant Category","Amount","Rewards"
 *   "Date","Posted Date","Reference Number","Activity Type","Status",
 *     "Transaction Card Number","Merchant Category","Merchant Name",
 *     "Merchant City","Merchant State/Province","Merchant Postal Code/Zip",
 *     "Amount","Rewards"
 *   "Date","Posted Date","Reference Number","Activity Type","Status",
 *     "Transaction Card Number","Merchant Category","Merchant Name",
 *     "Merchant City","Merchant State/Province","Merchant Country",
 *     "Merchant Postal Code/Zip","Amount","Rewards","Name on Card"
 *
 * One statement is emitted per transaction date, as before. Amounts are kept
 * positive; direction lives in transactionDC (D charge, C refund, B payment).
 *
 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
 */
/**
 * Raised when a WMMC export's header cannot be resolved to the columns the
 * importer needs, even after trying the known synonyms. The message names the
 * missing columns and every header it actually saw, so a format change can be
 * diagnosed from the error alone instead of from an empty import.
 */
class WmmcColumnHeaderMismatchException extends Exception {

	/** @var array<int,string> Canonical columns that could not be found. */
	protected $missingColumns;

	/** @var array<int,string> Header cells exactly as they were read. */
	protected $seenHeaders;

	/**
	 * @param array<int,string> $missingColumns Canonical columns that could not be found.
	 * @param array<int,string> $seenHeaders    Header cells as read from the file.
	 * @param string|null       $message        Optional message override.
	 */
	public function __construct(array $missingColumns, array $seenHeaders, $message = null)
	{
		$this->missingColumns = $missingColumns;
		$this->seenHeaders = $seenHeaders;

		if ($message === null) {
			$message = 'WMMC CSV column header mismatch: missing required column(s): '
				. implode(', ', $missingColumns)
				. '. Headers seen: ' . (count($seenHeaders) ? implode(' | ', $seenHeaders) : '(none)');
		}

		parent::__construct($message);
	}

	/**
	 * @return array<int,string>
	 */
	public function getMissingColumns()
	{
		return $this->missingColumns;
	}

	/**
	 * @return array<int,string>
	 */
	public function getSeenHeaders()
	{
		return $this->seenHeaders;
	}
}

class ro_wmmc_csv_parser extends parser {

	/**
	 * Header alias map: normalized header text => canonical column key.
	 *
	 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
	 *
	 * @return array<string,string>
	 */
	protected function headerAliases()
	{
		return array(
			'date' => 'date',
			'transaction date' => 'date',
			'trans date' => 'date',
			'posted date' => 'posteddate',
			'post date' => 'posteddate',
			'posting date' => 'posteddate',
			'reference number' => 'referencenumber',
			'reference' => 'referencenumber',
			'ref number' => 'referencenumber',
			'reference no' => 'referencenumber',
			'activity type' => 'activitytype',
			'status' => 'status',
			'transaction card number' => 'cardnumber',
			'card number' => 'cardnumber',
			'merchant category' => 'category',
			'category' => 'category',
			'merchant name' => 'merchant',
			'description' => 'merchant',
			'merchant' => 'merchant',
			'merchant city' => 'city',
			'city' => 'city',
			'merchant state/province' => 'province',
			'merchant state or province' => 'province',
			'merchant state or prov' => 'province',
			'merchant state' => 'province',
			'merchant province' => 'province',
			'state/province' => 'province',
			'state' => 'province',
			'province' => 'province',
			'merchant country' => 'country',
			'country' => 'country',
			'merchant postal code/zip' => 'postalcode',
			'merchant postal code' => 'postalcode',
			'merchant postal code or zip' => 'postalcode',
			'merchant zip' => 'postalcode',
			'merchant zip/postal code' => 'postalcode',
			'postal code/zip' => 'postalcode',
			'postal code' => 'postalcode',
			'zip' => 'postalcode',
			'amount' => 'amount',
			'transaction amount' => 'amount',
			'rewards' => 'rewards',
			'reward' => 'rewards',
			'name on card' => 'nameoncard',
			'cardholder name' => 'nameoncard',
		);
	}

	/**
	 * Columns the importer cannot work without.
	 *
	 * @return array<int,string>
	 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
	 */
	protected function requiredColumns()
	{
		return array('date', 'amount', 'merchant');
	}

	/**
	 * Second-tier header matching, used only for a required column that the
	 * alias map did not resolve. Each entry is the accepted spelling pattern
	 * plus, optionally, a pattern that rules a header out ("Posting Date"
	 * contains "date" but is not the transaction date).
	 *
	 * @return array<string,array{0:string,1:string|null}>
	 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
	 */
	protected function synonymPatterns()
	{
		return array(
			'date' => array(
				'/transaction date|trans date|transdate|value date|^date$|\bdate\b/',
				'/post|entry|due|statement|period/',
			),
			'amount' => array(
				'/amount|debit|credit|charge/',
				'/balance|rate/',
			),
			'merchant' => array(
				'/merchant ?name|^merchant$|description|payee|narrative|details/',
				'/category|card|city|state|country|postal|zip/',
			),
		);
	}

	/**
	 * Normalize one header cell: strip quotes/BOM, collapse whitespace, lower case.
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
	 * Map the raw header row to canonical column keys.
	 *
	 * Unknown headers keep a sanitized version of their own name so the row
	 * still combines cleanly; a canonical key that is already claimed by an
	 * earlier column is not overwritten.
	 *
	 * @param array<int,string> $cells Raw header cells.
	 * @return array<int,string> Column key per position.
	 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
	 */
	protected function mapHeader(array $cells)
	{
		$aliases = $this->headerAliases();
		$used = array();
		$mapped = array();

		foreach ($cells as $index => $cell) {
			$normalized = $this->normalizeHeaderCell($cell);
			$key = isset($aliases[$normalized]) ? $aliases[$normalized] : $normalized;
			$key = str_replace(' ', '', $key);

			if ($key === '') {
				$key = 'column' . $index;
			}
			if (isset($used[$key])) {
				$key .= '_' . $index;
			}

			$used[$key] = true;
			$mapped[$index] = $key;
		}

		return $mapped;
	}

	/**
	 * Map the header, then fill any still-missing REQUIRED column by synonym.
	 *
	 * @param array<int,string> $cells Raw header cells.
	 * @return array<int,string> Column key per position.
	 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
	 */
	protected function resolveColumns(array $cells)
	{
		$columns = $this->mapHeader($cells);
		$canonical = array_flip(array_values($this->headerAliases()));
		$patterns = $this->synonymPatterns();

		foreach ($this->requiredColumns() as $required) {
			if (in_array($required, $columns, true)) {
				continue;
			}
			if (!isset($patterns[$required])) {
				continue;
			}
			list($accept, $reject) = $patterns[$required];

			foreach ($cells as $index => $cell) {
				// Only reuse a position whose header did not resolve to a
				// known column; known columns keep their own meaning.
				if (isset($canonical[$columns[$index]]) || trim($cell) === '') {
					continue;
				}
				$normalized = $this->normalizeHeaderCell($cell);
				if (!preg_match($accept, $normalized)) {
					continue;
				}
				if ($reject !== null && preg_match($reject, $normalized)) {
					continue;
				}

				$columns[$index] = $required;
				break;
			}
		}

		return $columns;
	}

	/**
	 * Throw when a required column is still unresolvable.
	 *
	 * @param array<int,string> $columns Resolved column keys.
	 * @param array<int,string> $cells   Raw header cells.
	 * @return void
	 * @throws WmmcColumnHeaderMismatchException
	 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
	 */
	protected function assertRequiredColumns(array $columns, array $cells)
	{
		$missing = array();
		foreach ($this->requiredColumns() as $required) {
			if (!in_array($required, $columns, true)) {
				$missing[] = $required;
			}
		}

		if ($missing) {
			throw new WmmcColumnHeaderMismatchException($missing, array_values($cells));
		}
	}

	/**
	 * Read a canonical column from a parsed row, defaulting to ''.
	 *
	 * @param array<string,string> $row Parsed row.
	 * @param string               $key Canonical column key.
	 * @return string
	 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
	 */
	protected function column(array $row, $key)
	{
		return isset($row[$key]) ? (string) $row[$key] : '';
	}

	/**
	 * Normalize a date cell to ISO yyyy-mm-dd when it is recognizable.
	 *
	 * WMMC exports ISO dates; MM/DD/YYYY is accepted because older exports and
	 * hand-edited files use it.
	 *
	 * @param string $date Raw date cell.
	 * @return string Original value when it cannot be normalized.
	 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
	 */
	protected function normalizeDate($date)
	{
		$date = trim($date);
		$date = preg_split('/[ T]/', $date);
		$date = $date[0];

		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date)) {
			return $date;
		}
		if (preg_match('#^(\d{4})/(\d{1,2})/(\d{1,2})$#', $date, $m)) {
			return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
		}
		if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $date, $m)) {
			return sprintf('%04d-%02d-%02d', $m[3], $m[1], $m[2]);
		}

		return $date;
	}

	/**
	 * Parse an amount cell into a positive number plus its sign.
	 *
	 * Handles "$56.34", "-10.00", "1,234.56", "(10.00)" and "10.00-".
	 *
	 * @param string $amount Raw amount cell.
	 * @return array{0:float,1:bool} The absolute amount and whether it is negative.
	 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
	 */
	protected function parseAmount($amount)
	{
		$amount = trim((string) $amount);
		$negative = false;

		if (strncmp($amount, '-', 1) === 0 || substr($amount, -1) === '-') {
			$negative = true;
		}
		if (strlen($amount) > 1 && $amount[0] === '(' && substr($amount, -1) === ')') {
			$negative = true;
		}

		$number = preg_replace('/[^\d\.]/', '', $amount);

		return array((float) $number, $negative);
	}

	/**
	 * Import a WMMC CSV export.
	 *
	 * @param string $content     File contents.
	 * @param array  $static_data Form data (account, currency, bank_name).
	 * @param bool   $debug       Echo parse commentary.
	 * @return array<string,statement> Statements keyed by transaction date.
	 * @throws Exception When the header carries neither a date nor an amount column.
	 * @BABOK FR-IMP-001, FR-IMP-002 | BR-IMP-001 | UAT-IMP-001
	 */
	function parse($content, $static_data = array(), $debug = true) {
		$smts = array();
		$cardsSeen = array();

		$lines = preg_split('/\r\n|\r|\n/', (string) $content);
		$headerLine = array_shift($lines);

		if ($headerLine === null || trim($headerLine) === '') {
			throw new Exception('Empty WMMC CSV file.');
		}

		$headerCells = str_getcsv($headerLine);
		$columns = $this->resolveColumns($headerCells);
		$this->assertRequiredColumns($columns, $headerCells);

		if ($debug) {
			echo 'WMMC CSV columns: ' . implode(', ', $columns) . "\n";
		}

		foreach ($lines as $lineNumber => $line) {
			if (trim($line) === '') {
				continue;
			}

			$cells = str_getcsv($line);

			// Tolerate ragged rows (trailing empty cells, totals lines with
			// fewer columns) instead of letting array_combine() throw.
			$cells = array_slice(array_pad($cells, count($columns), ''), 0, count($columns));
			$row = array_combine($columns, array_map('strval', $cells));

			$sid = $this->normalizeDate($this->column($row, 'date'));
			if ($sid === '') {
				if ($debug) {
					echo 'WMMC CSV: skipping row without a date: ' . $line . "\n";
				}
				continue;
			}

			// One statement per (date, card): a single export can carry rows
			// from more than one card, and each statement must record its own
			// account rather than inheriting whichever card came first.
			$rowCard = trim($this->column($row, 'cardnumber'));
			$key = $sid . '|' . preg_replace('/[^0-9A-Za-z]/', '', $rowCard);
			$cardsSeen[$key] = $rowCard;

			if (empty($smts[$key])) {
				$smts[$key] = new statement;
				$smts[$key]->bank = $this->column($row, 'bankname') !== ''
					? $this->column($row, 'bankname')
					: (isset($static_data['bank_name']) && $static_data['bank_name'] !== ''
						? $static_data['bank_name']
						: 'WMMC');

				// The statement account is the bank account number. WMMC's export
				// only carries the masked card number, so fall back to that when
				// the form did not supply an account.
				if (isset($static_data['account']) && $static_data['account'] !== '') {
					$smts[$key]->account = $static_data['account'];
				} elseif ($rowCard !== '') {
					$smts[$key]->account = $rowCard;
				} else {
					$smts[$key]->account = 'WMMC';
				}

				$smts[$key]->currency = isset($static_data['currency']) && $static_data['currency'] !== ''
					? $static_data['currency']
					: 'CAD';
				$smts[$key]->timestamp = $sid;
				$smts[$key]->startBalance = '0';
				$smts[$key]->endBalance = '0';
				$smts[$key]->sequence = '0';

				if ($debug) {
					echo "WMMC CSV: adding statement for $sid\n";
				}
			}

			list($amount, $negative) = $this->parseAmount($this->column($row, 'amount'));
			$merchant = $this->column($row, 'merchant');
			$postedDate = $this->column($row, 'posteddate');
			$city = $this->column($row, 'city');
			$province = $this->column($row, 'province');
			$country = $this->column($row, 'country');
			$postalCode = $this->column($row, 'postalcode');
			$category = $this->column($row, 'category');
			$rewards = $this->column($row, 'rewards');
			$reference = $this->column($row, 'referencenumber');
			$status = $this->column($row, 'status');
			$cardNumber = $this->column($row, 'cardnumber');
			$nameOnCard = $this->column($row, 'nameoncard');

			$trz = new transaction;

			// TRF for transactions, COM for fees (ATM fees, commissions,
			// admin fees). A leading minus is a payment coming from a bank
			// account or a refund; everything else is a charge.
			if ($negative) {
				$isPayment = stripos($merchant, 'PAYMENT') !== false;
				$trz->transactionDC = $isPayment ? 'B' : 'C';
				$trz->transactionType = 'TRF';
			} else {
				$trz->transactionDC = 'D';
				$trz->transactionType = 'TRF';
			}

			$trz->transactionAmount = $amount;
			$trz->valueTimestamp = $sid;
			$trz->entryTimestamp = $postedDate !== '' ? $postedDate : $sid;
			$trz->transactionTitle2 = $city;
			$trz->transactionTitle3 = $province;
			$trz->transactionTitle4 = $country;
			$trz->city = $city;
			$trz->province = $province;
			$trz->country = $country;
			$trz->address = trim($city . "\n" . $province . "\n" . $country . "\n" . $postalCode);
			$smts[$key]->currency = $trz->currency = isset($static_data['currency']) && $static_data['currency'] !== ''
				? $static_data['currency']
				: 'CAD';
			$trz->transactionTitle5 = $postalCode . '  ';
			$trz->postalcode = $postalCode;
			$trz->rewards = $rewards;
			$trz->cardnumber = $cardNumber;
			$trz->nameoncard = $nameOnCard;
			$trz->transactionTitle6 = $category . '; ';
			$trz->category = $category . '; ';
			$trz->transactionTitle7 = $rewards . ' ';
			$trz->transactionTitle1 = $merchant;
			$trz->merchant = $merchant;
			$trz->transactionCode = $reference;
			if (strlen($trz->transactionCode) < 1 && stripos($merchant, 'INTER') !== false) {
				$trz->transactionCode = $trz->entryTimestamp . '-' . $merchant;
			}
			// Duplicate detection keys on transactionCode, so every row needs a
			// stable, non-empty one even when the bank left the reference blank.
			if ($trz->transactionCode === '') {
				$trz->transactionCode = $trz->entryTimestamp . '-' . substr(
					sha1($merchant . '|' . $amount . '|' . $trz->transactionDC . '|' . $rowCard),
					0,
					10
				);
			}
			$trz->reference = $reference;
			$trz->transactionCodeDesc = $status;
			$trz->status = $status;

			// The merchant name doubles as the matching account name; shorten
			// it only when the helper has actually been loaded (include_once
			// returns false on every call after the first, which used to make
			// the shortening depend on how many files the request parsed).
			if (function_exists('shorten_bankAccount_names')) {
				$trz->account = shorten_bankAccount_names($merchant);
			} else {
				$trz->account = $merchant;
			}
			$trz->accountName = $merchant;
			$trz->accountName1 = $merchant . ' ';
			$trz->accountName2 = trim($cardNumber . ' / ' . $nameOnCard);
			$trz->memo = $trz->transactionTitle1 . ' ' . $city . ', ' . $province . ';' . $country . ' ' . $postalCode . ';' . $trz->transactionTitle6;
			$trz->transactionTitle = $trz->transactionTitle1 . ';' . $city . ';' . $trz->transactionTitle6;

			$smts[$key]->addTransaction($trz);
		}

		// Finalise statement identity now that the whole file is known. A
		// single-card export keeps the historic "<date>-00000-0" id; a
		// multi-card export folds the card into the id so two cards on the
		// same day cannot be posted as one statement.
		$distinctCards = array_unique(array_values(array_filter($cardsSeen, 'strlen')));
		$multiCard = count($distinctCards) > 1;
		$final = array();
		$perDate = array();
		foreach ($smts as $key => $stmt) {
			$cardNumber = isset($cardsSeen[$key]) ? $cardsSeen[$key] : '';
			if ($multiCard && $cardNumber !== '') {
				$stmt->number = preg_replace('/[^0-9A-Za-z]/', '', $cardNumber);
			} else {
				$stmt->number = '00000';
			}
			$stmt->statementId = "{$stmt->timestamp}-{$stmt->number}-{$stmt->sequence}";
			$perDate[$stmt->timestamp] = isset($perDate[$stmt->timestamp]) ? $perDate[$stmt->timestamp] + 1 : 1;
		}

		// Keep the historic "keyed by date" contract when a date has a single
		// statement; only fall back to the statement id when two cards collide.
		foreach ($smts as $stmt) {
			$key = $perDate[$stmt->timestamp] > 1 ? $stmt->statementId : $stmt->timestamp;
			$final[$key] = $stmt;
		}

		return $final;
	}
}
