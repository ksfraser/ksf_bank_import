<?php
namespace Ksfraser\FaBankImport\Views;

class UploadFormView
{
    private $parsers;
    private $selectedParser;

    public function __construct(array $parsers, $selectedParser = null)
    {
        $this->parsers = $parsers;
        $this->selectedParser = $selectedParser;
    }

    public function getHtml(): string
    {
        $html = '<div id="doc_tbl">';
        $html .= '<form method="post" enctype="multipart/form-data">';
        $html .= '<table class="tablestyle">';
        $html .= '<tr><th colspan="2">' . _("Select File(s) and type") . '</th></tr>';
        // Format dropdown (SRP)
        $html .= '<tr><td>' . _("Format:") . '</td><td>';
        $parserDropdown = new ParserDropdownView($this->parsers, $this->selectedParser);
        $html .= $parserDropdown->getHtml();
        $html .= '</td></tr>';
        // File input
        $html .= '<tr><td>' . _("Files") . '</td><td><input type="file" name="files[]" multiple /></td></tr>';
        // Hidden state field - tells state machine to transition to parse_upload
        $html .= '<input type="hidden" name="state" value="parse_upload" />';
        // Upload button
        $html .= '<tr><td class="label">Upload</td><td><button type="submit" name="upload">' . _("Upload") . '</button></td></tr>';
        $html .= '</table>';
        $html .= '</form>';
        $html .= '</div>';
        return $html;
    }

    public function toHtml(): void
    {
        echo $this->getHtml();
    }

    public function render(array $parsers = null, $selectedParser = null): void
    {
        if ($parsers !== null) $this->parsers = $parsers;
        if ($selectedParser !== null) $this->selectedParser = $selectedParser;
        $this->toHtml();
    }
}
