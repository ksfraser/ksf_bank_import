<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\HtmlElement;

use Ksfraser\HTML\HtmlElementInterface;

/**//*************************************************
* Class for generating forms
*
*	https://www.w3schools.com/tags/tag_form.asp
*
* Forms can have the following elements:
*	input
*	textarea
*	button
*	select
*	option
*	optgroup
*	fieldset
*	label
*	output
*
* Forms can have the following attributes
*	accept-charset
*	action
*	autocomplete (on/off)
*	enctype
*	method (dialog, get, post)
*	name
*	novalidate
*	rel
*		external
*		help
*		license
*		next
*		nofollow
*		noopener
*		noreferrer
*		opener
*		prev
*		search
*	target
*		_blank
*		_self
*		_parent
*		_top
*******************************************************/

class HtmlForm extends HtmlElement
{
	protected $action;
	protected $method;

	public function __construct(HtmlElementInterface $data)
	{
		parent::__construct($data);
		$this->tag = "form";
	}

	/**
	 * Set the form method attribute (GET or POST)
	 *
	 * @param string $method HTTP method (get, post)
	 * @return self Fluent interface
	 * @throws \InvalidArgumentException If method is not valid
	 */
	public function setMethod(string $method): self {
		$valid = ['get', 'post'];
		$methodLower = strtolower($method);
		if (!in_array($methodLower, $valid, true)) {
			throw new \InvalidArgumentException("Invalid form method: '$method'. Only 'get' and 'post' are allowed.");
		}
		$this->addAttributeObject(new \Ksfraser\HTML\HtmlAttribute('method', $methodLower));
		return $this;
	}

	/**
	 * Set the form action attribute
	 *
	 * @param string $action Form action URL
	 * @return self Fluent interface
	 * @throws \InvalidArgumentException If action is not valid
	 */
	public function setAction(string $action): self {
		// Basic validation: must be a non-empty string
		if (trim($action) === '') {
			throw new \InvalidArgumentException("Invalid form action: must be a non-empty string.");
		}
		$this->addAttributeObject(new \Ksfraser\HTML\HtmlAttribute('action', htmlspecialchars($action)));
		return $this;
	}

	/**
	 * Set the form id attribute
	 *
	 * @param string $id Form id
	 * @return self Fluent interface
	 * @throws \InvalidArgumentException If id is not valid
	 */
	public function setId(string $id): self {
		// Valid HTML id: letters, digits, hyphens, underscores, no spaces, not empty
		if (!preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $id)) {
			throw new \InvalidArgumentException("Invalid form id: must start with a letter and contain only letters, digits, hyphens, or underscores.");
		}
		$this->addAttributeObject(new \Ksfraser\HTML\HtmlAttribute('id', $id));
		return $this;
	}

	/**
	 * Append child elements (form fields) to the form
	 *
	 * @param mixed ...$elements Variable number of elements to append
	 * @return self Fluent interface
	 */
	public function append(...$elements): self {
		foreach ($elements as $element) {
			if ($element instanceof \Ksfraser\HTML\HtmlElementInterface) {
				$this->addNested($element);
			} elseif (method_exists($element, 'getHtmlElement')) {
				$this->addNested($element->getHtmlElement());
			}
		}
		return $this;
	}

	/**
	 * Get HTML representation as string
	 *
	 * @return string The complete HTML form element
	 */
	public function getHtml(): string {
		return parent::getHtml();
	}

	/**
	 * Render the form to HTML string
	 *
	 * @return string The complete HTML form element
	 */
	public function render(): string {
		return $this->getHtml();
	}
}
