<?php

namespace Ksfraser\HTML;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\HTML_ROW_LABEL;

/**
 * Label Row Base Class
 * 
 * Abstract base class for label-data row elements.
 * Enforces that inheriting classes set label and data properties.
 * 
 * Follows Template Method Pattern: Constructor enforces structure
 * Follows Single Responsibility: Renders label-data rows
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 * @version 2.0.0
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
     * Get HTML representation as string
     * 
     * @return string HTML string
     */
    function getHtml(): string
    {
        return $this->row->getHtml();
    }
    
    /**
     * Render HTML directly to output
     * 
     * @return void
     */
    function toHtml(): void
    {
        $this->row->toHtml();
    }
}
