<?php
namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Composites\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * ProcessTransactionButtonRow - wraps a ProcessTransaction submit into a HtmlLabelRow
 * 
 * @package Views
 * @since 20251019
 */
class ProcessTransactionButtonRow implements HtmlElementInterface
{
    /**
     * @var HtmlLabelRow
     */
    protected $row;

    /**
     * Create process transaction button row for given index
     * 
     * @param int $index The transaction index
     */
    public function __construct(int $index)
    {
        $button = new SubmitButton("ProcessTransaction[$index]", _("Process"));
        $label = new HtmlString('');
        $this->row = new HtmlLabelRow($label, $button);
    }

    /**
     * Get HTML as string
     * 
     * @return string The HTML
     */
    public function getHtml(): string
    {
        return $this->row->getHtml();
    }

    /**
     * Output HTML directly to screen
     * 
     * @return void
     */
    public function toHtml(): void
    {
        $this->row->toHtml();
    }
}