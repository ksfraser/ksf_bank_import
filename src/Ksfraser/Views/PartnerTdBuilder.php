<?php

declare(strict_types=1);

namespace Ksfraser\Views;

use Ksfraser\HTML\HtmlFragment;
use Ksfraser\HTML\Elements\HtmlRaw;
use Ksfraser\HTML\Elements\HtmlTable;
use Ksfraser\HTML\Elements\HtmlTd;
use Ksfraser\HTML\HtmlAttribute;

/**
 * Builds partner/actions-column TD for 4-column transaction layout.
 *
 * Uses fragment composition (no callback rendering side effects).
 */
final class PartnerTdBuilder
{
    /**
     * @param HtmlFragment $contentFragment
     * @return HtmlTd
     */
    public function build(HtmlFragment $contentFragment): HtmlTd {
        $contentHtml = $contentFragment->getHtml();

        $table = new HtmlTable(new HtmlRaw($contentHtml));
        $table->addAttribute(new HtmlAttribute('class', 'tablestyle2'));
        $table->addAttribute(new HtmlAttribute('width', '100%'));

        $td = new HtmlTd($table);
        $td->addAttribute(new HtmlAttribute('width', '35%'));
        $td->addAttribute(new HtmlAttribute('valign', 'top'));

        return $td;
    }
}
