<?php
namespace Ksfraser\HTML\Button;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Elements\HtmlString;

class SubmitButton extends Button {
    public function __construct($label = null) {
        if ($label === null) {
            $label = new HtmlString('Submit');
        }
        parent::__construct($label);
        $this->setType('submit');
        $this->setName('submit_btn');
        $this->addClass('btn btn-primary btn-sm');
    }

}
