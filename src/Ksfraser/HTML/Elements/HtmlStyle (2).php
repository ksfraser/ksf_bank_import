<?php


namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;
use Ksfraser\HTML\HtmlElementInterface;


/**
 * HtmlStyle represents a <style> block element for internal CSS.
 * For inline styles, use StyleAttribute.
 */

class HtmlStyle extends HtmlElement
{
	/**
	 * Prevent adding children to <style> elements.
	 */
	public function addNested(HtmlElementInterface $element): self
	{
		throw new \InvalidArgumentException('HtmlStyle does not support child elements. Only raw CSS content is allowed. For attributes, use addAttributeObject.');
	}

	/**
	 * Prevent adding attributes to <style> elements except StyleAttribute.
	 */
	public function addAttributeObject(\Ksfraser\HTML\HtmlAttribute $attribute): self
	{
		if (!($attribute instanceof StyleAttribute)) {
			throw new \InvalidArgumentException('HtmlStyle only accepts StyleAttribute objects as attributes.');
		}
		return parent::addAttributeObject($attribute);
	}

	protected $cssContent;

	public function __construct(HtmlString $css)
	{
		parent::__construct(); // No child element
		$this->setTag('style');
		$this->cssContent = $css;
	}

	public function getHtml(): string
	{
		return '<style>' . $this->cssContent->getHtml() . '</style>';
	}
}
