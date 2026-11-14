<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: BR-009 / ARCH-009 / FR-009*/

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Elements\HtmlSubmit;
use Ksfraser\HTML\Elements\HtmlString;

/**
 * @BABOK FR-PROC-013 | BR-PROC-006 | UAT-PROC-013

 */
class AddVendorButton implements \Ksfraser\HTML\HtmlElementInterface
{
    protected $submit;

    /** * @BABOK FR-PROC-013 | BR-PROC-006 | UAT-PROC-013
 * @BABOK FR-PROC-013 | BR-PROC-006 | UAT-PROC-013
*/
    public function __construct(int $index)
    {
        /**
         * Composition chosen intentionally: this class configures and wraps
         * a `HtmlSubmit` element rather than extending it. See project
         * docs for rationale.
         */
        $this->submit = new HtmlSubmit(new HtmlString(_("AddVendor")));
        $this->submit->setName("AddVendor[$index]")->setClass('default');
    }

    /** * @BABOK FR-PROC-013 | BR-PROC-006 | UAT-PROC-013
 * @BABOK FR-PROC-013 | BR-PROC-006 | UAT-PROC-013
*/
    public function getHtml(): string
    {
        return $this->submit->getHtml();
    }

    /** * @BABOK FR-PROC-013 | BR-PROC-006 | UAT-PROC-013
 * @BABOK FR-PROC-013 | BR-PROC-006 | UAT-PROC-013
*/
    public function toHtml(): void
    {
        echo $this->getHtml();
    }
}
