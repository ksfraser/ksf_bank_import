<?php

/**
 * Matched Partner Type
 *
 * Represents a matched transaction partner type in the bank import system.
 *
 * @package    Ksfraser\PartnerTypes
 * @author     Original Author
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251019
 */

declare(strict_types=1);

namespace Ksfraser\PartnerTypes;

/**
 * Matched Partner Type
 *
 * Used for transactions that have been matched.
 * @BABOK FR-PROC-002, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
 */
class MatchedPartnerType extends AbstractPartnerType
{
    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
     */
    public function getShortCode(): string
    {
        return 'ZZ';
    }

    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
     */
    public function getLabel(): string
    {
        // Updated to match legacy process_statements.php label for backward compatibility
        return 'Matched';
    }

    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
     */
    public function getConstantName(): string
    {
        return 'MATCHED';
    }

    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
     */
    public function getPriority(): int
    {
        return 60;
    }

    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
     */
    public function getDescription(): ?string
    {
        return 'Matched transactions';
    }
    
    /**
     * @inheritDoc
     * 
     * Override to use 'displayMatchedExisting' instead of default 'displayMatched'
 * @BABOK FR-PROC-002, FR-PROC-009 | BR-PROC-001 | UAT-PROC-001
     */
    public function getStrategyMethodName(): string
    {
        return 'displayMatchedExisting';
    }
}
