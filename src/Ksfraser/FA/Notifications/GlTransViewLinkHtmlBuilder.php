<?php

/**
 * Transaction Link Flow (UML Activity)
 *
 * @uml
 * start
 * :Controller has direct GL route + label;
 * :GlTransViewLinkHtmlBuilder::build();  <<<< CURRENT FILE >>>>
 * :display_notification(renderedHtml);
 * stop
 * @enduml
 *
 * Responsibility in flow:
 * - Render a safe HTML anchor for already-determined GL transaction routes.
 */

declare(strict_types=1);

namespace Ksfraser\FA\Notifications;

use Ksfraser\HTML\Elements\HtmlA;

/**
 * SRP: build HTML anchor for GL transaction view links from transaction identifiers.
 */
final class GlTransViewLinkHtmlBuilder
{
    public static function build(int $transType, int $transNo, string $label = 'View Entry'): string
    {
        $url = TransactionLinkUrlBuilder::glTransView($transType, $transNo);

        try {
            $link = new HtmlA($url, $label);
            $link->setTarget('_blank');
            return $link->getHtml();
        } catch (\Throwable $e) {
            return "<a target=_blank href='" . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "'>"
                . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
                . "</a>";
        }
    }
}
