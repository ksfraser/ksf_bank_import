<?php

namespace Ksfraser\FaBankImport\View;

use Ksfraser\FaBankImport\Model\BiLineItemModel;

/**
 * View class for rendering line item data.
 * @BABOK FR-PROC-001, FR-PROC-002, FR-PROC-004, FR-PROC-005, FR-PROC-006, FR-PROC-007, FR-PROC-008, FR-PROC-009, FR-PROC-011, FR-PROC-012, FR-PROC-013, FR-VAL-004 | BR-PROC-001 | UAT-PROC-001
 */
class BiLineItemView
{
    /**
     * @var BiLineItemModel
     */
    private $lineItemModel;

    /**
     * Constructor.
     *
     * @param BiLineItemModel $lineItemModel
 * @BABOK FR-PROC-001, FR-PROC-002, FR-PROC-004, FR-PROC-005, FR-PROC-006, FR-PROC-007, FR-PROC-008, FR-PROC-009, FR-PROC-011, FR-PROC-012, FR-PROC-013, FR-VAL-004 | BR-PROC-001 | UAT-PROC-001
     */
    public function __construct(BiLineItemModel $lineItemModel)
    {
        $this->lineItemModel = $lineItemModel;
    }

    /**
     * Render the line item as an array.
     *
     * @return array
 * @BABOK FR-PROC-001, FR-PROC-002, FR-PROC-004, FR-PROC-005, FR-PROC-006, FR-PROC-007, FR-PROC-008, FR-PROC-009, FR-PROC-011, FR-PROC-012, FR-PROC-013, FR-VAL-004 | BR-PROC-001 | UAT-PROC-001
     */
    public function render(): array
    {
        return [
            'date' => $this->lineItemModel->getDate(),
            'description' => $this->lineItemModel->getDescription(),
            'amount' => $this->lineItemModel->getAmount(),
        ];
    }
}