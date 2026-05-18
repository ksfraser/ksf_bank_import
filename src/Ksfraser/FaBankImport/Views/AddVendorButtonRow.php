<?php
namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Composites\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * AddVendorButtonRow - composes an AddVendorButton into a HtmlLabelRow.
 * 
 * @package Views
 * @since 20251019
 */
class AddVendorButtonRow implements HtmlElementInterface
{
    /**
     * @var HtmlLabelRow
     */
    protected $row;

    /**
     * Create add vendor button row for given index
     * 
     * @param int $index The transaction index
     */
    public function __construct(int $index)
    {
        $button = new AddVendorButton($index);
        $labelText = new HtmlString("Add Vendor");
        $this->row = new HtmlLabelRow($labelText, $button);
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