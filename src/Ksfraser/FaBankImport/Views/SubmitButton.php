<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: ARCH-009 / BR-009 / FR-009*/

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Elements\HtmlSubmit;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK CLASS ROLLUP: SubmitButton — 3 methods; FR-009 (3); IR-001 (3)
 * @BABOK RTM: FR-009 / IR-001 / ARCH-008; file: Views/SubmitButton.php; coverage: docs/TestCases/ (file rollup)
 * SubmitButton - generic adapter that builds a HtmlSubmit element.
 * 
 * @package Views
 * @since 20251019
 */
class SubmitButton implements HtmlElementInterface
{
    /**
     * @var HtmlSubmit
     */
    protected $submit;

    /**
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: __construct
     * Create submit button with given name and label
     * 
     * @param string $name The button name
     * @param string $label The button label
     */
    public function __construct(string $name, string $label)
    {
        $this->submit = new HtmlSubmit(new HtmlString($label));
        $this->submit->setName($name)->setClass('default');
    }

    /**
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: getHtml
     * Get HTML as string
     * 
     * @return string The HTML
     */
    public function getHtml(): string
    {
        return $this->submit->getHtml();
    }

    /**
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: toHtml
     * Output HTML directly to screen
     * 
     * @return void
     */
    public function toHtml(): void
    {
        echo $this->getHtml();
    }
}
