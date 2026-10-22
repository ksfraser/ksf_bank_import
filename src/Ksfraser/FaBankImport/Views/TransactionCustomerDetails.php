<?php

namespace Ksfraser\FaBankImport\Views;

/**
 * @BABOK FR-VAL-004, FR-PROC-010 | BR-VAL-004 | UAT-PROC-001
 */
class TransactionCustomerDetails 
{
	protected $type;
	protected $type_no;
	protected $name;
	protected $br_name;
        /**
 * @BABOK FR-VAL-004, FR-PROC-010 | BR-VAL-004 | UAT-PROC-001
         */
        function __construct( $type, $type_no )
        {
		$this->type = $type;
		$this->type_no = $type_no;
                $this->get_customer_details();
	}
	/**
 * @BABOK FR-VAL-004, FR-PROC-010 | BR-VAL-004 | UAT-PROC-001
	 */
	protected function get_customer_details()
	{
                $details = get_customer_details_from_trans( $this->type, $this->type_no );
		$this->customer_name = $details['name'];
		$this->customer_branch = $details["br_name"];
		//...
	}
	/**
 * @BABOK FR-VAL-004, FR-PROC-010 | BR-VAL-004 | UAT-PROC-001
	 */
	function getLineitemMatchedCustomerDetails()
	{
		$str = " //Person " . $this->customer_name . "/" . $this->customer_branch;
		return $str;
	}
}
	
