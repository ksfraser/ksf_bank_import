<?php
namespace Ksfraser\HTML\ScriptHandlers;

use Ksfraser\HTML\Elements\HtmlScript;

/**
 * ReportScriptHandler - Script setup for report viewing operations
 * 
 * SRP: Single responsibility of building report handler scripts.
 * Provides modal viewer for report details and AJAX fetching.
 * 
 * @package Ksfraser\HTML\ScriptHandlers
 */
class ReportScriptHandler extends BaseScriptHandler {
    /**
     * Build handler scripts
     * 
     * @return HtmlScript[]
     */
    public function build(): array {
        return [
            // Load external report viewer script
            (new HtmlScript())->setAttribute('src', '/js/report-viewer.js')
        ];
    }
}
