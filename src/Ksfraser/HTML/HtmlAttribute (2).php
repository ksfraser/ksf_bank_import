<?php

namespace Ksfraser\HTML;

use Ksfraser\HTML\HtmlElementInterface;


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
	function __construct( $attribute, $value )
	{
		// Valid HTML attribute name: must start with a letter and contain only letters, digits, hyphens, or underscores
		if (!preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $attribute)) {
			throw new \InvalidArgumentException("Invalid attribute name: must start with a letter and contain only letters, digits, hyphens, or underscores.");
		}
		$this->attribute = $attribute;
		$this->value = $value;
	}
	/**
	 * Output the HTML string directly
	 * @return void
	 */
	public function toHtml(): void {
		echo $this->getHtml();
	}
	/**
	 * Get the HTML string for this attribute
	 * @return string The attribute in format: name="value"
	 */
	public function getHtml(): string {
		if (strlen($this->attribute) > 0) {
			$value = $this->value;
			if (is_object($value) && method_exists($value, 'getHtml')) {
				$value = $value->getHtml();
			} else {
				$value = (string)$value;
			}
			$html = $this->attribute . '="' . $value . '"';
			return $html;
		}
		return "";
	}

    public function getName(): string
    {
        return $this->attribute;
    }

    public function getValue(): string
    {
		$value = $this->value;
		if (is_object($value) && method_exists($value, 'getHtml')) {
			return $value->getHtml();
		}
		return (string)$value;
    }
}
