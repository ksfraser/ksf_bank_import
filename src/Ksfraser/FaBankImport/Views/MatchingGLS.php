<?php
namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Composites\HTML_ROW_LABEL;

/**
 * MatchingGLS - Display matching general ledger transactions
 * 
 * @package Views
 * @since 20251019
 */
class MatchingGLS implements HtmlElementInterface
{
    /**
     * @var HTML_ROW_LABEL
     */
    protected $row;

    /**
     * Create matching GLs display
     * 
     * @param object $bi_lineitem The bank import line item
     */
    public function __construct($bi_lineitem)
    {
        $data = new MatchingGLFactory($bi_lineitem);
        $label = "Matching GLs:";
        $this->row = new HTML_ROW_LABEL($data, $label, null, null);
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

/**
 * MatchingGLFactory - Factory for matching GL display data
 * 
 * @package Views
 * @since 20251019
 */
class MatchingGLFactory implements HtmlElementInterface
{
    /**
     * Create matching GL factory
     * 
     * @param object $bi_lineitem The bank import line item
     */
    public function __construct($bi_lineitem)
    {
    }

    /**
     * Get HTML as string
     * 
     * @return string Empty string for now
     */
    public function getHtml(): string
    {
        return '';
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