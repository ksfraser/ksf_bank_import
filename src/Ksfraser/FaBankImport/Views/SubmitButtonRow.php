<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: FR-009 / IR-001 / ARCH-008*/

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Composites\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * @BABOK FR-PROC-011 | BR-PROC-005 | UAT-PROC-010

 * SubmitButtonRow - composes a SubmitButton into a HtmlLabelRow.
 * 
 * @package Views
 * @since 20251019
 */
class SubmitButtonRow implements HtmlElementInterface
{
    /**
     * @var HtmlLabelRow
     */
    protected $row;

    /**
 * @BABOK FR-PROC-011 | BR-PROC-005 | UAT-PROC-010
     * Create submit button row with given name and label
     * 
     * @param string $name The button name
     * @param string $label The button label
     */
    public function __construct(string $name, string $label)
    {
        $button = new SubmitButton($name, $label);
        $labelElem = new HtmlString('');
        $this->row = new HtmlLabelRow($labelElem, $button);
    }

    /**
 * @BABOK FR-PROC-011 | BR-PROC-005 | UAT-PROC-010
     * Get HTML as string
     * 
     * @return string The HTML
     */
    public function getHtml(): string
    {
        return $this->row->getHtml();
    }

    /**
 * @BABOK FR-PROC-011 | BR-PROC-005 | UAT-PROC-010
     * Output HTML directly to screen
     * 
     * @return void
     */
    public function toHtml(): void
    {
        $this->row->toHtml();
    }
}
