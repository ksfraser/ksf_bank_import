<?php
namespace Ksfraser\HTML\Elements;

/**
 * Modal - Modal overlay component
 * 
 * Creates a modal dialog with overlay background.
 * SRP: Single responsibility of building modal structure.
 * 
 * @package Ksfraser\HTML\Elements
 */
class Modal extends Div {
    /**
     * @var string Modal ID
     */
    protected string $modalId = 'modal';
    
    /**
     * @var ModalContent Modal content component
     */
    protected ?ModalContent $content = null;
    
    /**
     * Constructor
     * 
     * @param string $id Modal ID
     */
    public function __construct(string $id = 'modal') {
        parent::__construct();
        $this->modalId = $id;
        $this->setId($id);
        $this->addClass('modal-overlay');
    }
    
    /**
     * Set modal content
     * 
     * @param ModalContent $content
     * @return self
     */
    public function setContent(ModalContent $content): self {
        $this->content = $content;
        return $this;
    }
    
    /**
     * Get modal content
     * 
     * @return ModalContent|null
     */
    public function getContent(): ?ModalContent {
        return $this->content;
    }
    
    /**
     * Render modal HTML
     * 
     * @return string
     */
    public function render(): string {
        if ($this->content) {
            $this->append($this->content);
        }
        return parent::render();
    }
    
    /**
     * Get stylesheet path for modal styling
     * 
     * @return string
     */
    public static function getStylesheetPath(): string {
        return '/css/modal.css';
    }
}
