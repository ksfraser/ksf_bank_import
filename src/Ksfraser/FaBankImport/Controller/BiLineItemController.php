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
        // The view reads the bank account details of the line, so the model
        // has to load them first.
        $this->model->getBankAccountDetails();

        // The view still uses the legacy method names.
        $this->view->display_left($this->model);
        $this->view->display_right($this->model);
    }
}