<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :TransTitle [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for TransTitle.
 */
namespace Ksfraser\FaBankImport;


use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Composites\LabelRowBase;

require_once( __DIR__ . '/../../HTML/Composites/LabelRowBase.php' );

class TransTitle extends LabelRowBase
{
	function __construct( $bi_lineitem )
	{
		$this->label = "Transaction Title:";
		$this->data =  $bi_lineitem->transactionTitle;
		parent::__construct( "" );
	}
}
