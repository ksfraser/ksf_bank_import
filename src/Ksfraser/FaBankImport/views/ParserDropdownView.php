<?php
namespace Ksfraser\FaBankImport\Views;

class ParserDropdownView
{
    private $parsers = [];
    private $selectedParser = '';

    public function __construct(array $parsers, string $selectedParser = '')
    {
        $this->parsers = $parsers;
        $this->setSelectedParser($selectedParser);
    }

    /**
     * Set the selected parser.
     * @param string $selectedParser
     */
    public function setSelectedParser(string $selectedParser): void
    {
        if ($selectedParser === '' || array_key_exists($selectedParser, $this->parsers)) {
            $this->selectedParser = $selectedParser;
        } else {
            throw new \InvalidArgumentException("Invalid parser selected: '" . $selectedParser . "'");
        }
    }

    /**
     * Get HTML for the parser dropdown (uses class property only).
     * @return string
     */
    public function getHtml(): string
    {
        $html = "<select name='parser'>";
        foreach ($this->parsers as $pid => $name) {
            $selected = ($pid === $this->selectedParser) ? 'selected' : '';
            $html .= "<option value='" . htmlspecialchars($pid) . "' $selected>" . htmlspecialchars($name) . "</option>";
        }
        $html .= "</select>";
        return $html;
    }

    /**
     * Echo HTML for the parser dropdown (uses class property only).
     */
    public function toHtml(): void
    {
        echo $this->getHtml();
    }

    /**
     * Render dropdown, optionally override selected parser.
     * @param string|null $selectedParser
     */
    public function render(string $selectedParser = null): void
    {
        if ($selectedParser !== null) {
            $this->setSelectedParser($selectedParser);
        }
        $this->toHtml();
    }
}
