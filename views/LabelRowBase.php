<?php

namespace Ksfraser\Html;

use  Ksfraser\Html\HtmlElementInterface;

require_once( __DIR__ .  '/HtmlElementInterface.php' );

class LabelRowBase implements HtmlElementInterface
//class LabelRowBase implements Ksfraser\Html\HtmlElementInterface
{
	protected $row;
	protected $label;
	protected $data;
	function __construct( $bi_lineitem )
	{
/* Inheriting class must set label and data!!
		$this->label = "";
		$this->data = "";
*/
		if( ! isset( $this->data ) )
		{
			throw new Exception( "data MUST be set by inheriting class!" );
		}
		if( ! isset( $this->label ) )
		{
			throw new Exception( "label MUST be set by inheriting class!" );
		}
		$this->row = new HtmlRowLabel( $this->label, $this->data,  null, null );
	}
	function getHtml()
	{
		$this->row->getHtml();
	}
	function toHtml()
	{
		$this->row->toHtml();
	}
}
