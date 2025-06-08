<?php
/*
 * Copyright (c) 2025 Kevin Fraser
 *
 * See the file LICENSE.txt for copying permission.
 */

namespace Ksfraser\HTML;

/**
 * an interface for rendering an HTML page, using the toHtml function.
 */
interface HtmlElementInterface {
        /**
         * Render HTML.
         * The Html is echoed directly into the output by echo'ing getHtml.
         */
        function toHtml();
        /**
         * Render HTML.
         * The Html is returned as a string.
         * Equivalent to __toString
         */
        function getHtml();
}
?>
