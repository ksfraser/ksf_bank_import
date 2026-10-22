<?php
namespace Ksfraser\FaBankImport\Controller;

use Ksfraser\FaBankImport\Model\BiLineItemModel;
use Ksfraser\FaBankImport\View\BiLineItemView;

/**
 * Controller class for managing line item interactions.
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
 */
class BiLineItemController
{
    private $model;
    private $view;

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    public function __construct(BiLineItemModel $model, BiLineItemView $view)
    {
        $this->model = $model;
        $this->view = $view;
    }

    /**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
     */
    public function display()
    {
        $this->view->displayLeft($this->model);
        $this->view->displayRight($this->model);
    }
}