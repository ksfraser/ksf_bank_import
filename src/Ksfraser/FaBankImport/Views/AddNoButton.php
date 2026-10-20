<?php
// @BABOK Referenced in RTM for domain layer (BR-001 through BR-012, ARCH-001 through ARCH-010)
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Elements\HtmlSubmit;
use Ksfraser\HTML\Elements\HtmlString;

/**
 * @BABOK CLASS ROLLUP: AddNoButton — 3 methods; FR-009 (3); IR-001 (3)
 * @BABOK RTM: FR-009 / IR-001 / ARCH-008; file: Views/AddNoButton.php; coverage: docs/TestCases/ (file rollup)
 */
class AddNoButton implements \Ksfraser\HTML\HtmlElementInterface
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
        $this->submit = new HtmlSubmit(new HtmlString(_("There is nothing to add")));
        $this->submit->setName("AddNo[$index]")->setClass('default');
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
