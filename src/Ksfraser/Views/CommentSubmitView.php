<?php
/**
 * CommentSubmitView Component
 * 
 * Renders a comment input field and submit button using HTML library classes.
 * This component follows the Option B pattern, returning HtmlFragment for composability.
 * 
 * @package Ksfraser
 * @subpackage Views
 * @version 1.0.0
 */

namespace Ksfraser\Views;

require_once(__DIR__ . '/../HTML/HtmlFragment.php');
require_once(__DIR__ . '/../HTML/HtmlAttribute.php');
require_once(__DIR__ . '/../HTML/Elements/HtmlString.php');
require_once(__DIR__ . '/../HTML/Elements/HtmlInput.php');
require_once(__DIR__ . '/../HTML/Elements/HtmlSubmit.php');
require_once(__DIR__ . '/../HTML/Composites/HtmlLabelRow.php');

use Ksfraser\HTML\HtmlFragment;
use Ksfraser\HTML\HtmlAttribute;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\Elements\HtmlInput;
use Ksfraser\HTML\Elements\HtmlSubmit;
use Ksfraser\HTML\Composites\HtmlLabelRow;

/**
 * CommentSubmitView class
 * 
 * Displays a comment input field with label and a submit button in separate label rows.
 * 
 * Expected data structure:
 * [
 *     'id' => int,                    // Line item ID
 *     'comment' => string,            // Comment/memo text
 *     'comment_label' => string,      // Label for comment field (localized)
 *     'button_name' => string,        // Submit button name attribute
 *     'button_label' => string        // Submit button label (localized)
 * ]
 */
class CommentSubmitView
{
    /**
     * @var array Component data
     */
    private $data;
    
    /**
     * Constructor
     * 
     * @param array $data Component data (see class docblock for structure)
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }
    
    /**
     * Render the comment and submit button rows
     * 
     * Returns an HtmlFragment containing:
     * - Label row with comment input field
     * - Label row with submit button (empty label)
     * 
     * @return HtmlFragment The rendered HTML fragment
     */
    public function render(): HtmlFragment
    {
        $fragment = new HtmlFragment();
        
        // Add comment row
        $fragment->addChild($this->renderCommentRow());
        
        // Add submit button row
        $fragment->addChild($this->renderSubmitRow());
        
        return $fragment;
    }
    
    /**
     * Display the component (backward compatibility)
     * 
     * Outputs the rendered HTML directly to output buffer.
     * 
     * @return void
     */
    public function display(): void
    {
        echo $this->render()->toHtml();
    }
    
    /**
     * Render the comment input row
     * 
     * @return HtmlLabelRow The comment row with label and input field
     */
    private function renderCommentRow(): HtmlLabelRow
    {
        $label = new HtmlString($this->data['comment_label']);
        
        // Create comment input field
        $commentInput = new HtmlInput("text");
        $commentInput->setName("comment_{$this->data['id']}");
        $commentInput->setValue($this->data['comment']);
        
        // Set size attribute based on comment length (like text_input does)
        $size = max(strlen($this->data['comment']), 20); // Minimum 20
        $commentInput->addAttribute(new HtmlAttribute("size", (string)$size));
        
        // Set title attribute (for tooltip)
        $commentInput->addAttribute(new HtmlAttribute("title", $this->data['comment_label']));
        
        return new HtmlLabelRow($label, $commentInput);
    }
    
    /**
     * Render the submit button row
     * 
     * @return HtmlLabelRow The submit button row with empty label
     */
    private function renderSubmitRow(): HtmlLabelRow
    {
        // Empty label (as per FA pattern)
        $label = new HtmlString('');
        
        // Create submit button with label
        $buttonLabel = new HtmlString($this->data['button_label']);
        $submitButton = new HtmlSubmit($buttonLabel);
        $submitButton->setName($this->data['button_name']);
        $submitButton->setClass('default');
        
        return new HtmlLabelRow($label, $submitButton);
    }
}
