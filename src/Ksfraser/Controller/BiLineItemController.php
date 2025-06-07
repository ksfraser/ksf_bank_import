<?php 

namespace Ksfraser\FaBankImport\Controller;

//These might be in the model class and not needed here...
use Ksfraser\common\GenericFaInterface;
use Ksfraser\common\Defines;



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
//WHERE are we getting ->id from?  It comes from $trz['id']
	if( isset( $_POST["partnerId_" . $this->id] ) )
	{
		$this->model->set( "partnerId", $_POST["partnerId_" . $this->id] );
	}

    }

    public function display()
    {
	$this->model->getBankAccountDetails();
        $this->view->displayLeft($this->model);
        $this->view->displayRight($this->model);
    }
        /**//*******************************************************************
        * Display SUPPLIER partner type
        *
        ************************************************************************/
        function displaySupplierPartnerType()
        {
		$match = $this->model->seekPartnerByBankAccount( PT_SUPPLIER );
		$this->view->displaySupplierPartnerType( $this->model->get( "id" ), $match );
        }
        /**//*******************************************************************
        * Display CUSTOMER partner type
        *
        ************************************************************************/
        function displayCustomerPartnerType()
        {
		$match = $this->model->seekPartnerByBankAccount( PT_CUSTOMER );
		$this->view->displayCustomerPartnerType( $this->model->get( "id" ), $match );
        }

}
