<?php
namespace Ksfraser\HTML\Button;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Elements\HtmlString;

class CloseButton extends Button {
    public function __construct($label = null) {
        if ($label === null) {
            $label = new HtmlString('Close');
        }
        parent::__construct($label);
        $this->setName('close_btn');
        $this->addClass('btn btn-secondary btn-sm');
    }

}
