<?php
 * Referenced in RTM for domain layer (BR-001 through BR-012, ARCH-001 through ARCH-010)
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\FaBankImport\Model\BiLineItemModel;

/**
 * View class for rendering line item data.
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
     */
    public function __construct(BiLineItemModel $lineItemModel)
    {
        $this->lineItemModel = $lineItemModel;
    }

    /**
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
