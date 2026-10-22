<?php

namespace Ksfraser\HTML;

use Ksfraser\HTML\HtmlElementInterface;


/**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 */
class HtmlAttribute implements HtmlElementInterface
{
	/**********
	* Common attributes:
	*	Class
	*		Can be repeated amongst elements
	*	ID
	*		ID must be used uniquely
	*	Style
	*/
	protected $attribute;	//Key
	protected $value;	//Value
	/**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
	 */
	function __construct( $attribute, $value )
	{
		$this->attribute = $attribute;
		$this->value = $value;
	}
	/**
	 * Output the HTML string directly
	 * @return void
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
	 */
	public function toHtml(): void {
		echo $this->getHtml();
	}
	/**
	 * Get the HTML string for this attribute
	 * @return string The attribute in format: name="value"
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
	 */
	public function getHtml(): string {
		if( strlen( $this->attribute ) > 0 )
		{
			$html = $this->attribute . '="' . $this->value . '"';
			return $html;
		}	
		return "";
	}

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getName(): string
    {
        return $this->attribute;
    }

    /**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
