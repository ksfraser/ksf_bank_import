<?php
namespace Ksfraser\HTML\Factory;

use Ksfraser\HTML\Factory\ActionLink;
use Ksfraser\HTML\Elements\HtmlA;

/**
 * ListLink - Specialized link for list/index actions
 * 
 * Extends ActionLink with defaults for list operations.
 * 
 * @package Ksfraser\HTML\Factory
 */
class ListLink extends ActionLink {
        public static function make(?string $text = null, array $params = []): self {
            return new static($text, $params);
        }
    protected string $label = 'List';
    public function __construct(?string $text = null, array $params = []) {
        parent::__construct();
        $this->setAction('list', $params);
        $this->setText($text ?? $this->label);
    }
}
