<?php
namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\Elements\HtmlSubmit;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlElementInterface;

/**
 * SubmitButton - generic adapter that builds a HtmlSubmit element.
 * 
 * @package Views
 * @since 20251019
 */
class SubmitButton implements HtmlElementInterface
{
    /**
     * @var HtmlSubmit
     */
    protected $submit;

    /**
     * Create submit button with given name and label
     * 
     * @param string $name The button name
     * @param string $label The button label
     */
    public function __construct(string $name, string $label)
    {
        $this->submit = new HtmlSubmit(new HtmlString($label));
        $this->submit->setName($name)->setClass('default');
    }

    /**
     * Get HTML as string
     * 
     * @return string The HTML
     */
    public function getHtml(): string
    {
        return $this->submit->getHtml();
    }

    /**
     * Output HTML directly to screen
     * 
     * @return void
     */
    public function toHtml(): void
    {
        echo $this->getHtml();
    }
}