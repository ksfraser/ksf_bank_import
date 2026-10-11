<?php

namespace Ksfraser\FaBankImport;


class TransactionCustomerDetails 
{
	protected $type;
	protected $type_no;
	protected $name;
	protected $br_name;
        function __construct( $type, $type_no )
        {
		$this->type = $type;
		$this->type_no = $type_no;
                $this->get_customer_details();
	}
	protected function get_customer_details()
	{
                $details = get_customer_details_from_trans( $this->type, $this->type_no );
		$this->customer_name = $details['name'];
		$this->customer_branch = $details["br_name"];
		//...
	}
	function getLineitemMatchedCustomerDetails()
	{
		$str = " //Person " . $this->customer_name . "/" . $this->customer_branch;
		return $str;
	}
}
	
