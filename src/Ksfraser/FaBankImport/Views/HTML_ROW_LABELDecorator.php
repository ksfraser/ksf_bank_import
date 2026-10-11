<?php
namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Composites\HTML_ROW_LABEL;

/**
 * HTML_ROW_LABELDecorator - Decorator for HTML_ROW_LABEL
 * 
 * @package Views
 * @since 20251019
 */
class HTML_ROW_LABELDecorator implements HtmlElementInterface
{
    /**
     * @var HTML_ROW_LABEL
     */
    protected $HTML_LABEL_ROW;

    /**
     * Create label row decorator
     * 
     * @param string $data The cell content
     * @param string $label The label text
     * @param int $width The label width percentage (default 25)
     * @param string $class The CSS class (default 'label')
     */
    public function __construct(string $data, string $label, int $width = 25, string $class = 'label')
    {
        $this->HTML_LABEL_ROW = new HTML_ROW_LABEL($data, $label, $width, $class);
    }

    /**
     * Output HTML directly to screen
     * 
     * @return void
     */
    public function toHtml(): void
    {
        $this->HTML_LABEL_ROW->toHtml();
    }

    /**
     * Get HTML as string
     * 
     * @return string The HTML
     */
    public function getHtml(): string
    {
        return $this->HTML_LABEL_ROW->getHtml();
    }
}