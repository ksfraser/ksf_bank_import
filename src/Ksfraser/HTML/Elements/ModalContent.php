<?php
namespace Ksfraser\HTML\Elements;

/**
 * ModalContent - Modal content area component
 * 
 * Creates the content container within a modal.
 * SRP: Single responsibility of building modal content structure.
 * 
 * @package Ksfraser\HTML\Elements
 */
class ModalContent extends Div {
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->addClass('modal-content');
    }
    
    /**
     * Get stylesheet path for modal content styling
     * 
     * @return string
     */
    public static function getStylesheetPath(): string {
        return '/css/modal.css';
    }
}
