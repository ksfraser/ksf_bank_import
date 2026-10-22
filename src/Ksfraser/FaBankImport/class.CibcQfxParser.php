<?php

/**
 * Compatibility shim.
 *
 * The QFX/OFX parser classes (AbstractQfxParser, CibcQfxParser, PmcQfxParser,
 * ManuQfxParser, QfxParserFactory) were previously copied verbatim into five
 * files, each declaring all five global-namespace classes. That produced five
 * times the uncovered code and a redeclaration hazard.
 *
 * The single canonical definition now lives in class.AbstractQfxParser.php;
 * this file keeps the historical file name working by loading it.
 *
 * @see class.AbstractQfxParser.php
 */

require_once __DIR__ . '/class.AbstractQfxParser.php';
