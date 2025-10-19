<?php

namespace Ksfraser\HTML;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\HTML_ROW_LABEL;

class LabelRowBase implements HtmlElementInterface
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
        $this->row = new HTML_ROW_LABEL($this->label, $this->data, null, null);
    }
    
    function getHtml()
    {
        return $this->row->getHtml();
    }
    
    function toHtml()
    {
        $this->row->toHtml();
    }
}
