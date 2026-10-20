<?php

/**
 * @BABOK RTM: service layer (FR-001 through FR-018); module coverage tracking: docs/Requirements/
 */

namespace Ksfraser\FaBankImport\Services;

/**
 * @BABOK RTM: BR-001 / BR-002 / FR-008 / ARCH-001; file: Services/TransactionCounter.php; coverage: docs/TestCases/ (file rollup)
 * TransactionCounter
 *
 * Single Responsibility: Handle counting of transactions based on WHERE clause filters
 * This class encapsulates the responsibility of fetching transaction counts from the database
 */
class TransactionCounter
{
	/**
 * @BABOK BR-001 / BR-002 / FR-008 / ARCH-001 — method: count
	 * Count transactions matching the given WHERE clause
	 *
	 * @param string $where_clause The WHERE clause to filter transactions (e.g., " WHERE t.valueTimestamp >= '2025-01-01'...")
	 * @return int The total count of matching transactions
	 * @throws \Exception If the database query fails
	 */
	public function count($where_clause)
	{
		$count_sql = "SELECT COUNT(*) as cnt FROM " . TB_PREF . "bi_transactions t " . $where_clause;
		$count_result = db_query($count_sql, 'Could not get transaction count');
		$count_row = db_fetch($count_result);
		return (int)$count_row['cnt'];
	}
}
