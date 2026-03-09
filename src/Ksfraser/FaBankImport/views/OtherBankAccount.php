<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :OtherBankAccount [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for OtherBankAccount.
 */
namespace Ksfraser\FaBankImport;


use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Composites\LabelRowBase;

require_once( __DIR__ . '/../../HTML/Composites/LabelRowBase.php' );

class OtherBankAccount extends LabelRowBase
{
	function __construct( $bi_lineitem )
	{
		$this->label = "Other Bank Account:";
		$this->data = $bi_lineitem->otherBankAccount . ' / '. $bi_lineitem->otherBankAccountName;
		parent::__construct( "" );
	}
}
