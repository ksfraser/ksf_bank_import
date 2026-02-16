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

namespace Ksfraser\FA\Links;

use Ksfraser\HTML\Elements\HtmlA;
use Ksfraser\HTML\HtmlAttribute;

/**
 * SRP: build HTML anchor for GL transaction view links from transaction identifiers.
 */
final class GlTransViewLinkHtmlBuilder
{
    /**
     * @param array<string, scalar|null> $attributes Optional anchor attributes (e.g. target, class, rel, title)
     * @param array<string, scalar> $extraQueryParams Additional query parameters to append to the URL
     */
    public static function build(int $transType, int $transNo, string $label = 'View Entry', array $attributes = [], array $extraQueryParams = []): string
    {
        $url = TransactionLinkUrlBuilder::glTransView($transType, $transNo, $extraQueryParams);
        $target = isset($attributes['target']) ? (string)$attributes['target'] : '_blank';

        try {
            $link = new HtmlA($url, $label);
            $link->setTarget($target);

            foreach ($attributes as $name => $value) {
                $normalizedName = strtolower((string)$name);
                if (
                    $normalizedName === 'target'
                    || $normalizedName === 'href'
                    || $normalizedName === 'trans_no'
                    || $normalizedName === 'trans_type'
                    || $normalizedName === 'type_id'
                    || $value === null
                ) {
                    continue;
                }
                $link->addAttribute(new HtmlAttribute((string)$name, (string)$value));
            }

            return $link->getHtml();
        } catch (\Throwable $e) {
            $attrParts = [];
            $attrParts[] = 'target="' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '"';
            $attrParts[] = 'href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"';

            foreach ($attributes as $name => $value) {
                $normalizedName = strtolower((string)$name);
                if (
                    $normalizedName === 'target'
                    || $normalizedName === 'href'
                    || $normalizedName === 'trans_no'
                    || $normalizedName === 'trans_type'
                    || $normalizedName === 'type_id'
                    || $value === null
                ) {
                    continue;
                }
                $attrParts[] = htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8')
                    . '="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"';
            }

            return '<a ' . implode(' ', $attrParts) . '>'
                . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
                . '</a>';
        }
    }
}
