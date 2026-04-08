<?php
namespace Ksfraser\HTML\Factory;

use Ksfraser\HTML\Factory\ActionLink;
use Ksfraser\HTML\Elements\HtmlA;

/**
 * DeleteLink - Specialized link for delete actions
 * 
 * Extends ActionLink with defaults for delete operations.
 * Requires an ID parameter and includes confirmation prompt.
 * 
 * @package Ksfraser\HTML\Factory
 */
class DeleteLink extends ActionLink {
    public static function make($id, ?string $text = null, array $params = []): self {
        return new static($id, $text, $params);
    }
    public function setConfirmMessage(string $message): self {
        $this->confirmMessage = $message;
        $this->setAttribute('onclick', 'return confirm("' . addslashes($this->confirmMessage) . '");');
        return $this;
    }

    public function getConfirmMessage(): string {
        return $this->confirmMessage;
    }
    protected string $label = 'Delete';
    protected string $confirmMessage = 'Are you sure you want to delete this item?';
    public function __construct($id, ?string $text = null, array $params = []) {
        parent::__construct();
        $allParams = array_merge(['id' => $id], $params);
        $this->setAction('delete', $allParams);
        $this->setText($text ?? $this->label);
        $this->setAttribute('onclick', 'return confirm("' . addslashes($this->confirmMessage) . '");');
    }
}
