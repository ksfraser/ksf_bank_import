<?php

namespace Ksfraser\HTML\Composites;

use Ksfraser\HTML\Elements\HtmlTable;
use Ksfraser\HTML\Composites\HTML_ROW;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlElementInterface;
use Exception;

/**
 * HTML_TABLE - Backward-compatible wrapper for table rendering
 * 
 * This class provides backward compatibility with legacy code that uses HTML_TABLE.
 * It wraps the modern HtmlTable class and maintains the old interface:
 * - appendRow($row) accepts HTML_ROW objects or strings
 * - toHtml() outputs directly to screen (legacy behavior)
 * - getHtml() returns HTML string (modern approach)
 * 
 * The original HTML_TABLE used FrontAccounting's start_table() and end_table()
 * functions. This wrapper maintains that behavior while using modern OOP.
 * 
 * @deprecated This class exists for backward compatibility only.
 *             New code should use HtmlTable directly with composition.
 * @see \Ksfraser\HTML\HtmlTable
 * 
 * @package Ksfraser\HTML
 * @since 20251019 - Converted to wrapper
 * @version 20251019.1 - Fixed $rows bug from original (line 127 used $rows instead of $this->rows)
 */
class HTML_TABLE implements HtmlElementInterface
{
    /**
     * Array of HTML_ROW objects
     * @var array<HTML_ROW>
     */
    protected $rows;
    
    /**
     * Table style (FrontAccounting tablestyle constant)
     * @var int
     */
    protected $style;
    
    /**
     * Table width percentage
     * @var int
     */
    protected $width;
    
    /**
     * Create a new table
     * 
     * @param int|null $style FrontAccounting table style (default TABLESTYLE2 = 2, null = 2)
     * @param int $width Width percentage (default 100)
     */
    public function __construct(?int $style = null, int $width = 100)
    {
        $this->style = $style ?? 2;  // null becomes 2
        $this->width = $width;
        $this->rows = array();
    }
    
    /**
     * Append a row to the table
     * 
     * Accepts either an HTML_ROW object, HtmlElementInterface, or a string.
     * If a string is passed, it will be wrapped in an HTML_ROW automatically.
     * 
     * @param HTML_ROW|HtmlElementInterface|string $row The row to append
     * @throws Exception If parameter is neither HTML_ROW, HtmlElementInterface, nor string
     * @return void
     */
    public function appendRow($row): void
    {
        if (is_object($row)) {
            if (is_a($row, HTML_ROW::class)) {
                $this->rows[] = $row;
            } elseif (is_a($row, HtmlElementInterface::class)) {
                // Wrap HtmlElementInterface in HTML_ROW
                $this->rows[] = new HTML_ROW($row);
            } else {
                throw new Exception("Passed in class is not an HTML_ROW or child type!");
            }
        } elseif (is_string($row)) {
            $r = new HTML_ROW($row);
            $this->rows[] = $r;
        } else {
            throw new Exception("Passed in data for a row is neither a class nor a string");
        }
    }
    
    /**
     * Output the table HTML directly to screen
     * 
     * This method maintains legacy behavior by echoing output directly.
     * Uses FrontAccounting's start_table() and end_table() if available,
     * otherwise generates standard HTML table tags.
     * 
     * NOTE: In the original class.bi_lineitem.php, line 127 had a bug:
     *       it used `foreach( $rows as $row )` instead of `foreach( $this->rows as $row )`
     *       This has been fixed here.
     * 
     * @return void
     */
    public function toHtml(): void
    {
        // Start table using FA function or plain HTML
        if (function_exists('start_table')) {
            start_table($this->style, "width='" . $this->width . "%'");
        } else {
            echo "<table class='tablestyle" . $this->style . "' width='" . $this->width . "%'>\n";
        }
        
        // Output each row (FIXED: was $rows, now $this->rows)
        foreach ($this->rows as $row) {
            $row->toHtml();
        }
        
        // End table using FA function or plain HTML
        if (function_exists('end_table')) {
            end_table();
        } else {
            echo "</table>\n";
        }
    }
    
    /**
     * Get the table HTML as a string
     * 
     * Modern approach that returns HTML instead of echoing.
     * This allows for better testability and composition.
     * 
     * @return string The table HTML
     */
    public function getHtml(): string
    {
        ob_start();
        $this->toHtml();
        return ob_get_clean();
    }
}
