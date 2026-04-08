<?php
namespace Ksfraser\HTML\Factory;

use Ksfraser\HTML\Factory\ActionLink;
use Ksfraser\HTML\Elements\HtmlA;

/**
 * CreateLink - Specialized link for create actions
 * 
 * Extends ActionLink with defaults for create operations.
 * 
 * @package Ksfraser\HTML\Factory
 */
class CreateLink extends ActionLink {
        public static function make(?string $text = null, array $params = []): self {
            return new static($text, $params);
        }
    protected string $label = 'Create';
    public function __construct(?string $text = null, array $params = []) {
        parent::__construct();
        $this->setAction('create', $params);
        $this->setText($text ?? $this->label);
    }
}
