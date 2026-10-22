<?php

/**
 * Manual Settlement Partner Type
 *
 * Represents a manual settlement partner type in the bank import system.
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
 * Manual Settlement Partner Type
 *
 * Used for manually settling transactions to existing GL entries.
 * @BABOK FR-PROC-002, FR-PROC-008 | BR-PROC-001 | UAT-PROC-001
 */
class ManualSettlementPartnerType extends AbstractPartnerType
{
    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-008 | BR-PROC-001 | UAT-PROC-001
     */
    public function getShortCode(): string
    {
        return 'MA';
    }

    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-008 | BR-PROC-001 | UAT-PROC-001
     */
    public function getLabel(): string
    {
        // Updated to match legacy process_statements.php label for backward compatibility
        return 'Manual settlement';
    }

    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-008 | BR-PROC-001 | UAT-PROC-001
     */
    public function getConstantName(): string
    {
        return 'MANUAL_SETTLEMENT';
    }

    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-008 | BR-PROC-001 | UAT-PROC-001
     */
    public function getPriority(): int
    {
        return 50;
    }

    /**
     * @inheritDoc
 * @BABOK FR-PROC-002, FR-PROC-008 | BR-PROC-001 | UAT-PROC-001
     */
    public function getDescription(): ?string
    {
        return 'Manually match to existing GL entries';
    }
    
    /**
     * @inheritDoc
     * 
     * Manual Settlement uses displayManualSettlement method
 * @BABOK FR-PROC-002, FR-PROC-008 | BR-PROC-001 | UAT-PROC-001
     */
    public function getStrategyMethodName(): string
    {
        return 'displayManualSettlement';
    }
}
