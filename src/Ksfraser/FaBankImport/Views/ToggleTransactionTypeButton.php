<?php
namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Elements\HtmlSubmit;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlElementInterface;

/**
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
     * Get HTML as string
     * 
     * @return string The HTML
     */
    public function getHtml(): string
    {
        return $this->submit->getHtml();
    }

    /**
     * Output HTML directly to screen
     * 
     * @return void
     */
    public function toHtml(): void
    {
        echo $this->getHtml();
    }
}