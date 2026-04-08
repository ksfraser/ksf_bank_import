<?php
<namespace Ksfraser\FaBankImport\Controller;

use Ksfraser\FaBankImport\Model\BiLineItemModel;
use Ksfraser\FaBankImport\View\BiLineItemView;

/**
 * Controller class for managing line item interactions.
 */
class BiLineItemController
{
    private $model;
    private $view;

    public function __construct(BiLineItemModel $model, BiLineItemView $view)
    {
        $this->model = $model;
        $this->view = $view;
    }

    public function display()
    {
        $this->view->displayLeft($this->model);
        $this->view->displayRight($this->model);
    }
}
