<?php
namespace Ksfraser\HTML;

use Ksfraser\HTML\Elements\HtmlString;

/**
 * HtmlScriptLanguage
 *
 * Abstract base for valid script language wrappers (e.g., JS, JSON, TypeScript, VBScript).
 * Extends HtmlString for consistent string behavior.
 */
abstract class HtmlScriptLanguage extends HtmlString {
    public function __construct(string $content) {
        parent::__construct($content);
    }
}
