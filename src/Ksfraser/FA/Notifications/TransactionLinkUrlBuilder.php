<?php

/**
 * Transaction Link Flow (UML Activity)
 *
 * @uml
 * start
 * :Caller gathers resultData;
 * :TransactionResultLinkPresenter::displayFromResultData();
 * :TransactionLinkBuilder::buildFromResultData();
 * :TransactionLinkUrlBuilder::build();  <<<< CURRENT FILE >>>>
 * :TransactionLinkNotificationDisplayer::display();
 * :display_notification(anchor);
 * stop
 * @enduml
 *
 * Responsibility in flow:
 * - Convert routing parameters into a route-specific app-relative URL.
 */

declare(strict_types=1);

namespace Ksfraser\FA\Notifications;

/**
 * SRP: Build canonical transaction view URLs for notification links.
 */
final class TransactionLinkUrlBuilder
{
    public static function glTransView(int $transType, int $transNo): string
    {
        return "../../gl/view/gl_trans_view.php?type_id={$transType}&trans_no={$transNo}";
    }

    /**
     * @return array{view_gl_link: string}
     */
    public static function glTransViewLinkData(int $transType, int $transNo): array
    {
        return [
            'view_gl_link' => self::glTransView($transType, $transNo),
        ];
    }
}
