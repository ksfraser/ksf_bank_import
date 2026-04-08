<?php
namespace Ksfraser\HTML\Factory;

use Ksfraser\HTML\Factory\ActionLink;
use Ksfraser\HTML\Elements\HtmlA;

/**
 * EditLink - Specialized link for edit actions
 * 
 * Extends ActionLink with defaults for edit operations.
 * Requires an ID parameter.
 * 
 * @package Ksfraser\HTML\Factory
 */
class EditLink extends ActionLink {
        public static function make($id, ?string $text = null, array $params = []): self {
            return new static($id, $text, $params);
        }
    protected string $label = 'Edit';
    public function __construct($id, ?string $text = null, array $params = []) {
        parent::__construct();
        $allParams = array_merge(['id' => $id], $params);
        $this->setAction('edit', $allParams);
        $this->setText($text ?? $this->label);
    }
}
