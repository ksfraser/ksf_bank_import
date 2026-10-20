<?php
// @BABOK Referenced in RTM for domain layer (BR-001 through BR-012, ARCH-001 through ARCH-010)
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Elements\HtmlSubmit;
use Ksfraser\HTML\Elements\HtmlString;

/**
 * @BABOK RTM: FR-009 / IR-001 / ARCH-008; file: Views/AddCustomerButton.php; coverage: docs/TestCases/ (file rollup)
 */
class AddCustomerButton implements \Ksfraser\HTML\HtmlElementInterface
{
    protected $submit;

    /** @BABOK FR-009 / IR-001 / ARCH-008 — method: __construct */
    public function __construct(int $index)
    {
        /**
         * Composition chosen intentionally: this class configures and wraps
         * a `HtmlSubmit` element rather than extending it. See project
         * docs for rationale.
         */
        $this->submit = new HtmlSubmit(new HtmlString(_("AddCustomer")));
        $this->submit->setName("AddCustomer[$index]")->setClass('default');
    }

    /** @BABOK FR-009 / IR-001 / ARCH-008 — method: getHtml */
    public function getHtml(): string
    {
        return $this->submit->getHtml();
    }

    /** @BABOK FR-009 / IR-001 / ARCH-008 — method: toHtml */
    public function toHtml(): void
    {
        echo $this->getHtml();
    }
}
