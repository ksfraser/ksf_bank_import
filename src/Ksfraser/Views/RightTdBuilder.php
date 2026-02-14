<?php

declare(strict_types=1);

namespace Ksfraser\Views;

use Ksfraser\HTML\HtmlFragment;
use Ksfraser\HTML\Elements\HtmlRaw;
use Ksfraser\HTML\Elements\HtmlTable;
use Ksfraser\HTML\Elements\HtmlTd;
use Ksfraser\HTML\HtmlAttribute;

/**
 * Builds legacy right-column TD for 2-column transaction layout.
 *
 * TODO(SRP-HTML): Remove HtmlOB after legacy right-column renderers
 * return HtmlElement/HtmlFragment objects instead of output side effects.
 */
final class RightTdBuilder
{
    /**
     * @param HtmlFragment $contentFragment
     * @return HtmlTd
     */
    public function build(HtmlFragment $contentFragment): HtmlTd
    {
        $contentHtml = $contentFragment->getHtml();

        $table = new HtmlTable(new HtmlRaw($contentHtml));
        $table->addAttribute(new HtmlAttribute('class', 'tablestyle2'));
        $table->addAttribute(new HtmlAttribute('width', '100%'));

        $td = new HtmlTd($table);
        $td->addAttribute(new HtmlAttribute('width', '50%'));
        $td->addAttribute(new HtmlAttribute('valign', 'top'));

        return $td;
    }
}
