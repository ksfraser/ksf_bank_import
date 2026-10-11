<?php
namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Composites\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * ToggleTransactionRow - composes a ToggleTransactionTypeButton into
 * a HtmlLabelRow for use in legacy views.
 * 
 * @package Views
 * @since 20251019
 */
class ToggleTransactionRow implements HtmlElementInterface
{
    /**
     * @var HtmlLabelRow
     */
    protected $row;

    /**
     * Create toggle transaction row for given index
     * 
     * @param int $index The transaction index
     */
    public function __construct(int $index)
    {
        $button = new ToggleTransactionTypeButton($index);
        $label = new HtmlString("Toggle Transaction Type Debit/Credit");
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