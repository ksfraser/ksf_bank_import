<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Domain\Service;

use Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\ReconciliationSession;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\StatementOcr;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\BankTransactionDto;

/**
 * Contract for matching engine implementations.
 *
 * Given a parsed statement and a list of bank transactions, produce a
 * ReconciliationSession in 'pending' status with matched pairs and
 * lists of unmatched items on both sides.
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Domain\Service
 * @author  Kevin Fraser
 */
interface MatchingEngineInterface
{
    /**
     * @param StatementOcr         $statement
     * @param BankTransactionDto[] $bankTransactions
     * @return ReconciliationSession  Always in STATUS_PENDING.
     */
    public function match(StatementOcr $statement, array $bankTransactions): ReconciliationSession;
}
