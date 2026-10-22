<?php
namespace Ksfraser\HTML;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * A trait that defines an object which can have HTML children
 * 
 * @author Kevin Fraser <kevin@ksfraser.ca>
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 */
trait HTMLChildrenTrait {
	
	/**
	 * @var HtmlElementInterface[]
	 */
	protected $children = array();
	
	/**
	 * @return HtmlElementInterface[]
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
	 */
	public function getChildren() {
		return $this->children;
	}

	/**
	 * Sets the list of children of this element.
	 * 
	 * @param HtmlElementInterface[] $children
	 * @return $this
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
	 */
	public function setChildren(array $children) {
		foreach( $children as $child )
		{
			$this->addChild( $child );
		}
		return $this;
	}
	
	/**
	 * Adds a child to this element.
	 * 
	 * @param HtmlElementInterface $child
	 * @return \Mouf\Html\Tags\ChildrenTrait
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
	 */
	public function addChild(HtmlElementInterface $child) {
		$this->children[] = $child;
		return $this;
	}
	
	/**
	 * Renders HTML attributes.
	 * 
	 * @return string
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
	 */
	protected function renderChildren() {
		ob_start();
		foreach ($this->children as $child) {
			if ($child != null) {
				$child->toHtml();
			}
		}
		return ob_get_clean();
	}
	/**
 * @BABOK FR-PROC-001, FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
	 */
	protected function renderChildrenHtml()
	{
		$html = "";
		foreach ($this->children as $child) {
			if ($child != null) {
				$html .= $child->getHtml();
			}
		}
		return $html;
	}
}
