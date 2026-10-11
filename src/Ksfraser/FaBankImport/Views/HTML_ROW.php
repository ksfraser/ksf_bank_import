<?php
namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Elements\HtmlTableRow;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * HTML_ROW - Wraps data in an HTML table row
 * 
 * @package Views
 * @since 20251019
 */
class HTML_ROW implements HtmlElementInterface
{
    /**
     * @var HtmlTableRow
     */
    protected $row;

    /**
     * Create table row with given data
     * 
     * @param string $data The cell content
     */
    public function __construct(string $data)
    {
        $this->row = new HtmlTableRow(new HtmlString($data));
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

    /**
     * Get HTML as string
     * 
     * @return string The HTML
     */
    public function getHtml(): string
    {
        return $this->row->getHtml();
    }
}