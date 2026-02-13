<?php

/**
 * Test-only compatibility shims.
 *
 * Some tests (and legacy code) still reference older class locations/names.
 * This file provides safe aliases so unit tests can run against the current
 * refactored implementations.
 */

declare(strict_types=1);

// NOTE: class_alias will trigger autoload for the source class.

// Old: Ksfraser\HTML\Elements\HtmlLabelRow -> New: Ksfraser\HTML\Composites\HtmlLabelRow
if (!class_exists(\Ksfraser\HTML\Elements\HtmlLabelRow::class) && class_exists(\Ksfraser\HTML\Composites\HtmlLabelRow::class)) {
    class_alias(\Ksfraser\HTML\Composites\HtmlLabelRow::class, \Ksfraser\HTML\Elements\HtmlLabelRow::class);
}

// Old: Ksfraser\HTML\HTML_ROW_LABEL -> New: Ksfraser\HTML\Composites\HTML_ROW_LABEL
if (!class_exists(\Ksfraser\HTML\HTML_ROW_LABEL::class) && class_exists(\Ksfraser\HTML\Composites\HTML_ROW_LABEL::class)) {
    class_alias(\Ksfraser\HTML\Composites\HTML_ROW_LABEL::class, \Ksfraser\HTML\HTML_ROW_LABEL::class);
}

// Old: Ksfraser\HTML\Elements\HtmlAttribute -> New: Ksfraser\HTML\HtmlAttribute
if (!class_exists(\Ksfraser\HTML\Elements\HtmlAttribute::class) && class_exists(\Ksfraser\HTML\HtmlAttribute::class)) {
    class_alias(\Ksfraser\HTML\HtmlAttribute::class, \Ksfraser\HTML\Elements\HtmlAttribute::class);
}

// Legacy global class name used by ViewBILineItems: HTML_TABLE
if (!class_exists('HTML_TABLE') && class_exists(\Ksfraser\HTML\Composites\HTML_TABLE::class)) {
    class_alias(\Ksfraser\HTML\Composites\HTML_TABLE::class, 'HTML_TABLE');
}

// Legacy global classes used directly by many tests.
if (!class_exists('bi_lineitem')) {
    $lineitemFile = __DIR__ . '/../class.bi_lineitem.php';
    if (is_file($lineitemFile)) {
        require_once $lineitemFile;
    }
}

if (!class_exists('ViewBILineItems')) {
    $viewFile = __DIR__ . '/../class.ViewBiLineItems.php';
    if (is_file($viewFile)) {
        require_once $viewFile;
    }
}

