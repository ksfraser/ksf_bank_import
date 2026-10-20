<?php
/** @BABOK See docs/Requirements/ for module coverage tracking - Views layer (FR-009 / ARCH-009 / BR-009) */
namespace Ksfraser\FaBankImport\Views;



use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Composites\HTML_ROW_LABEL;

/**
 * @BABOK CLASS ROLLUP: HTML_ROW_LABELDecorator — 3 methods; FR-009 (3); IR-001 (3)
 * @BABOK RTM: FR-009 / IR-001 / ARCH-008; file: Views/HTML_ROW_LABELDecorator.php; coverage: docs/TestCases/ (file rollup)
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
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: __construct
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
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: toHtml
     * Output HTML directly to screen
     * 
     * @return void
     */
    public function toHtml(): void
    {
        $this->HTML_LABEL_ROW->toHtml();
    }

    /**
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: getHtml
     * Get HTML as string
     * 
     * @return string The HTML
     */
    public function getHtml(): string
    {
        return $this->HTML_LABEL_ROW->getHtml();
    }
}