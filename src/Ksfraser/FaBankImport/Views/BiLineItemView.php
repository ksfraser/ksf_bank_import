<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: ARCH-009 / FR-009 / BR-003*/

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\FaBankImport\Model\BiLineItemModel;

/**
 * @BABOK FR-PROC-010, FR-PROC-011 | BR-PROC-001 | UAT-PROC-001

 * View class for rendering line item data.
 */
class BiLineItemView
{
    /**
     * @var BiLineItemModel
     */
    private $lineItemModel;

    /**
 * @BABOK FR-PROC-010, FR-PROC-011 | BR-PROC-001 | UAT-PROC-001
     * Constructor.
     *
     * @param BiLineItemModel $lineItemModel
     */
    public function __construct(BiLineItemModel $lineItemModel)
    {
        $this->lineItemModel = $lineItemModel;
    }

    /**
 * @BABOK FR-PROC-010, FR-PROC-011 | BR-PROC-001 | UAT-PROC-001
     * Render the line item as an array.
     *
     * @return array
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
