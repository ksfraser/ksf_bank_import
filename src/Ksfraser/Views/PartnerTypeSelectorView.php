<?php
/**
 * PartnerTypeSelectorView Component
 * 
 * Renders a partner type dropdown selector using HTML library classes.
 * This component follows the Option B pattern, returning HtmlFragment for composability.
 * 
 * @package Ksfraser
 * @subpackage Views
 * @version 1.0.0
 */

namespace Ksfraser\Views;

require_once(__DIR__ . '/../HTML/HtmlFragment.php');
require_once(__DIR__ . '/../HTML/Elements/HtmlString.php');
require_once(__DIR__ . '/../HTML/Elements/HtmlSelect.php');
require_once(__DIR__ . '/../HTML/Elements/HtmlOption.php');
require_once(__DIR__ . '/../HTML/Composites/HtmlLabelRow.php');

use Ksfraser\HTML\HtmlFragment;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\Elements\HtmlSelect;
use Ksfraser\HTML\Elements\HtmlOption;
use Ksfraser\HTML\Composites\HtmlLabelRow;

/**
 * PartnerTypeSelectorView class
 * 
 * Displays a partner type dropdown selector with label.
 * Optionally adds JavaScript to auto-submit form on change.
 * 
 * Expected data structure:
 * [
 *     'id' => int,                           // Line item ID
 *     'selected_value' => string,            // Currently selected partner type
 *     'options' => array,                    // Associative array [value => label]
 *     'label' => string,                     // Label text (localized)
 *     'select_submit' => bool                // Whether to auto-submit on change
 * ]
 */
class PartnerTypeSelectorView
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
     * Render the partner type selector row
     * 
     * Returns an HtmlFragment containing a label row with the select element.
     * 
     * @return HtmlFragment The rendered HTML fragment
     */
    public function render(): HtmlFragment
    {
        $fragment = new HtmlFragment();
        $fragment->addChild($this->renderSelectorRow());
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
     * Render the selector row with label
     * 
     * @return HtmlLabelRow The selector row with label and select element
     */
    private function renderSelectorRow(): HtmlLabelRow
    {
        $label = new HtmlString($this->data['label']);
        $select = $this->createSelectElement();
        
        return new HtmlLabelRow($label, $select);
    }
    
    /**
     * Create the select element with options
     * 
     * @return HtmlSelect The configured select element
     */
    private function createSelectElement(): HtmlSelect
    {
        $selectName = "partnerType[{$this->data['id']}]";
        $select = new HtmlSelect($selectName);
        
        // Add options with selected state
        $select->addOptionsFromArray(
            $this->data['options'],
            $this->data['selected_value']
        );
        
        // Add onchange handler if select_submit is enabled
        if ($this->data['select_submit']) {
            $select->setAttribute('onchange', 'this.form.submit()');
        }
        
        return $select;
    }
}
