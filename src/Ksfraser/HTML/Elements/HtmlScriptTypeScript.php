<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\Elements\HtmlScript;
use Ksfraser\HTML\Typescript\HtmlTypeScriptString;

/**
 * HtmlScriptTypeScript - Represents a <script> tag for TypeScript content
 *
 * Generates: <script type="application/typescript">...</script>
 */
class HtmlScriptTypeScript extends HtmlScript
{
    public function __construct(HtmlTypeScriptString $content)
    {
        parent::__construct('application/typescript', $content);
    }
}
