<?php
// @BABOK Referenced in RTM for domain layer (BR-001 through BR-012, ARCH-001 through ARCH-010)
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\HTML;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Composites\HTML_ROW_LABEL;

/**
 * @BABOK RTM: FR-009 / IR-001 / ARCH-008; file: Views/LabelRowBase.php; coverage: docs/TestCases/ (file rollup)
 * Label Row Base Class
 * 
 * Abstract base class for label-data row elements.
 * Enforces that inheriting classes set label and data properties.
 * 
 * Follows Template Method Pattern: Constructor enforces structure
 * Follows Single Responsibility: Renders label-data rows
 * 
 * @author Kevin Fraser
 * @since 20250119
 * @version 20250119
 */
abstract class LabelRowBase implements HtmlElementInterface
{
    /** @var HTML_ROW_LABEL The HTML row object */
    protected $row;
    
    /** @var string The label text for the row */
    protected $label;
    
    /** @var mixed The data content for the row */
    protected $data;
    
    /**
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: __construct
     * Constructor
     * 
     * Validates that inheriting classes have set label and data.
     * Creates HTML_ROW_LABEL instance.
     * 
     * @param mixed $bi_lineitem Line item data object
     * @throws \RuntimeException If label or data not set by child class
     */
    function __construct($bi_lineitem)
    {
        // Inheriting class must set label and data in their constructor
        // before calling parent::__construct()
        
        if (!isset($this->data)) {
            throw new \RuntimeException("data MUST be set by inheriting class!");
        }
        
        if (!isset($this->label)) {
            throw new \RuntimeException("label MUST be set by inheriting class!");
        }
        
        $this->row = new HTML_ROW_LABEL($this->label, $this->data, null, null);
    }
    
    /**
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: getHtml
     * Get HTML representation as string
     * 
     * @return string HTML string
     */
    function getHtml(): string
    {
        return $this->row->getHtml();
    }
    
    /**
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: toHtml
     * Render HTML directly to output
     * 
     * @return void
     */
    function toHtml(): void
    {
        $this->row->toHtml();
    }
}
