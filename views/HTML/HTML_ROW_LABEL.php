<?php

namespace Ksfraser\HTML;

class HTML_ROW_LABEL extends HtmlTableRow
{
    public function __construct(string|HtmlElementInterface $data, string $label, int $width = 25, string $class = 'label')
    {
        $content = is_string($data) ? new HtmlString($data) : $data;
        parent::__construct($content);
        
        $this->addAttribute(new HtmlAttribute('label', $label));
        $this->addAttribute(new HtmlAttribute('width', $width));
        $this->addAttribute(new HtmlAttribute('class', $class));
    }
}

