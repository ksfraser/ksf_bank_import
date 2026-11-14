<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: ARCH-009 / BR-009 / FR-009*/

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Elements\HtmlSubmit;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK FR-PROC-011 | BR-PROC-005 | UAT-PROC-010

 * ToggleTransactionTypeButton - small adapter that builds a HtmlSubmit
 * element for toggling transaction debit/credit.
 * 
 * @package Views
 * @since 20251019
 */
class ToggleTransactionTypeButton implements HtmlElementInterface
{
    /**
     * @var HtmlSubmit
     */
    protected $submit;

    /**
 * @BABOK FR-PROC-011 | BR-PROC-005 | UAT-PROC-010
     * Create toggle button for transaction at given index
     * 
     * @param int $index The transaction index
     */
    public function __construct(int $index)
    {
        $this->submit = new HtmlSubmit(new HtmlString(_("ToggleTransaction")));
        $this->submit->setName("ToggleTransaction[$index]")->setClass('default');
    }

    /**
 * @BABOK FR-PROC-011 | BR-PROC-005 | UAT-PROC-010
     * Get HTML as string
     * 
     * @return string The HTML
     */
    public function getHtml(): string
    {
        return $this->submit->getHtml();
    }

    /**
 * @BABOK FR-PROC-011 | BR-PROC-005 | UAT-PROC-010
     * Output HTML directly to screen
     * 
     * @return void
     */
    public function toHtml(): void
    {
        echo $this->getHtml();
    }
}
