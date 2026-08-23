<?php
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Models;

/**
 * Contract for finding existing transactions matching an imported line item.
 *
 * Decouples bi_lineitem from ksf_modules_common (MatchingJEs -> fa_gl chain).
 *
 * @since 20260822
 */
interface MatchingTransactionsFinderInterface
{
    /**
     * Find existing JEs/transactions matching the given line item.
     *
     * @param object $lineItem Imported line item (bi_lineitem or compatible).
     * @return array Match rows (type, type_no, tran_date, account, amount, score, ...).
     */
    public function findFor(object $lineItem): array;
}
