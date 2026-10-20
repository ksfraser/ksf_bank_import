<?php
// @BABOK Referenced in RTM for domain layer (BR-001 through BR-012, ARCH-001 through ARCH-010)
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\FaBankImport;


/**
 * @BABOK RTM: FR-009 / IR-001 / ARCH-008; file: Views/TransactionCustomerDetails.php; coverage: docs/TestCases/ (file rollup)
 */
class TransactionCustomerDetails 
{
	protected $type;
	protected $type_no;
	protected $name;
	protected $br_name;
        /** @BABOK FR-009 / IR-001 / ARCH-008 — method: __construct */
        function __construct( $type, $type_no )
        {
		$this->type = $type;
		$this->type_no = $type_no;
                $this->get_customer_details();
	}
	/** @BABOK FR-009 / IR-001 / ARCH-008 — method: get_customer_details */
	protected function get_customer_details()
	{
                $details = get_customer_details_from_trans( $this->type, $this->type_no );
		$this->customer_name = $details['name'];
		$this->customer_branch = $details["br_name"];
		//...
	}
	/** @BABOK FR-009 / IR-001 / ARCH-008 — method: getLineitemMatchedCustomerDetails */
	function getLineitemMatchedCustomerDetails()
	{
		$str = " //Person " . $this->customer_name . "/" . $this->customer_branch;
		return $str;
	}
}
	
