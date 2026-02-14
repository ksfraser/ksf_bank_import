<?php

declare(strict_types=1);

namespace Ksfraser\Views;

use Ksfraser\HTML\HtmlFragment;
use Ksfraser\HTML\Elements\HtmlRaw;
use Ksfraser\HTML\Elements\HtmlTable;
use Ksfraser\HTML\Elements\HtmlTd;
use Ksfraser\HTML\HtmlAttribute;

/**
 * Builds legacy left-column TD for 2-column transaction layout.
 *
 * TODO(SRP-HTML): Remove HtmlOB after legacy display helpers return
 * HtmlElement/HtmlFragment trees.
 */
final class LeftTdBuilder
{
    /**
     * @param string $labelRowsHtml
     * @param HtmlFragment $contentFragment
     * @return HtmlTd
     */
    public function build(string $labelRowsHtml, HtmlFragment $contentFragment): HtmlTd
    {
        $complexHtml = $contentFragment->getHtml();

        $tableContent = new HtmlRaw($labelRowsHtml . $complexHtml);
        $innerTable = new HtmlTable($tableContent);
        $innerTable->addAttribute(new HtmlAttribute('class', TABLESTYLE2));
        $innerTable->addAttribute(new HtmlAttribute('width', '100%'));

        $td = new HtmlTd($innerTable);
        $td->addAttribute(new HtmlAttribute('width', '50%'));
        $td->addAttribute(new HtmlAttribute('valign', 'top'));

        return $td;
    }
}
