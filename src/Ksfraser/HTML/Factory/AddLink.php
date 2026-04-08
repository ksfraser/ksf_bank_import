<?php
namespace Ksfraser\HTML\Factory;

use Ksfraser\HTML\Factory\ActionLink;
use Ksfraser\HTML\Elements\HtmlA;

/**
 * AddLink - Specialized link for add actions
 * 
 * Extends ActionLink with defaults for add operations.
 * Similar to Create but uses "add" action name.
 * 
 * @package Ksfraser\HTML\Factory
 */
class AddLink extends ActionLink {
        public static function make(?string $text = null, array $params = []): self {
            return new static($text, $params);
        }
    /**
     * @var string Default label for add links
     */
    protected string $label = 'Add';
    
    /**
     * Constructor
     * 
     * @param string|null $text Link text (uses label if null)
     * @param array $params Additional query parameters
     */
    public function __construct(?string $text = null, array $params = []) {
        parent::__construct();
        $this->setAction('add', $params);
        $this->setText($text ?? $this->label);
    }
}
