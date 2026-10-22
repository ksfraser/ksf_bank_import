<?php
/*
 * Copyright (c) 2025 Kevin Fraser
 *
 * See the file LICENSE.txt for copying permission.
 */

namespace Ksfraser\HTML;

/**
 * Interface for HTML elements
 * Provides methods for both direct output and string return of HTML
 * @BABOK FR-IMP-001, FR-PROC-001, FR-PROC-002 | BR-IMP-001 | UAT-IMP-001
 */
interface HtmlElementInterface {
    /**
     * Returns HTML string representation
 * @BABOK FR-IMP-001, FR-PROC-001, FR-PROC-002 | BR-IMP-001 | UAT-IMP-001
     */
    public function getHtml(): string;

    /**
     * Outputs HTML directly
     */
    /**
     * Render the element as HTML.
     *
     * Outputs the HTML representation of this element.
     * Implementations should emit the markup (for example using echo or print)
     * rather than returning it.
     *
     * @return void
 * @BABOK FR-IMP-001, FR-PROC-001, FR-PROC-002 | BR-IMP-001 | UAT-IMP-001
     */
    public function toHtml(): void;
}
?>
