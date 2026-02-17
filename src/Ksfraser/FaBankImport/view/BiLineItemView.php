<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :BiLineItemView [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for BiLineItemView.
 */
namespace Ksfraser\FaBankImport\View;

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
