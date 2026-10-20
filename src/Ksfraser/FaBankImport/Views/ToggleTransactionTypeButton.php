<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: ARCH-009 / BR-009 / FR-009*/

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Elements\HtmlSubmit;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK CLASS ROLLUP: ToggleTransactionTypeButton — 3 methods; FR-009 (3); IR-001 (3)
 * @BABOK RTM: FR-009 / IR-001 / ARCH-008; file: Views/ToggleTransactionTypeButton.php; coverage: docs/TestCases/ (file rollup)
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
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: __construct
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
