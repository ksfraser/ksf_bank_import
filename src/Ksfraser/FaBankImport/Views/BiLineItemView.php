<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: ARCH-009 / FR-009 / BR-003*/

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\FaBankImport\Model\BiLineItemModel;

/**
 * @BABOK CLASS ROLLUP: BiLineItemView — 2 methods; FR-009 (2); IR-001 (2)
 * @BABOK RTM: FR-009 / IR-001 / ARCH-008; file: Views/BiLineItemView.php; coverage: docs/TestCases/ (file rollup)
 * View class for rendering line item data.
 */
class BiLineItemView
{
    /**
     * @var BiLineItemModel
     */
    private $lineItemModel;

    /**
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: __construct
     * Constructor.
     *
     * @param BiLineItemModel $lineItemModel
     */
    public function __construct(BiLineItemModel $lineItemModel)
    {
        $this->lineItemModel = $lineItemModel;
    }

    /**
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: render
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
