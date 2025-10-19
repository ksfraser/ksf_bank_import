<?php

namespace Ksfraser\HTML;

class HTML_ROW_LABEL extends HtmlTableRow
{
    /**
     * Create a table row with a label
     * 
     * @param string|HtmlElementInterface $data The content (string or HTML element)
     * @param string $label The label text
     * @param int $width The width percentage (default 25)
     * @param string $class The CSS class (default 'label')
     */
    public function __construct($data, string $label, int $width = 25, string $class = 'label')
    {
        $content = is_string($data) ? new HtmlString($data) : $data;
        parent::__construct($content);
        
        $this->addAttribute(new HtmlAttribute('label', $label));
        $this->addAttribute(new HtmlAttribute('width', $width));
        $this->addAttribute(new HtmlAttribute('class', $class));
    }
}

