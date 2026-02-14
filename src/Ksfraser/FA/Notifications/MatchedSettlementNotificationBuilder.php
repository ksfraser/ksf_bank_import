<?php

/**
 * Transaction Link Flow (UML Activity)
 *
 * @uml
 * start
 * :Caller handles MATCH settlement;
 * :MatchedSettlementNotificationBuilder::buildResultData();  <<<< CURRENT FILE >>>>
 * :TransactionResultLinkPresenter::displayFromResultData();
 * :TransactionLinkBuilder builds link payload;
 * :TransactionLinkNotificationDisplayer::display();
 * :display_notification(anchor);
 * stop
 * @enduml
 *
 * Responsibility in flow:
 * - Create normalized notification payload/message for MATCH-settlement links.
 */

declare(strict_types=1);

namespace Ksfraser\FA\Notifications;

/**
 * SRP: build MATCH-settlement notification payload from transaction identifiers.
 */
final class MatchedSettlementNotificationBuilder
{
    /**
     * @return array{message: string, linkData: array{view_gl_link: string}, context: array{context: string}}
     */
    public static function build(int $transType, int $transNo): array
    {
        return [
            'message' => self::buildMessage($transType, $transNo),
            'linkData' => TransactionLinkUrlBuilder::glTransViewLinkData($transType, $transNo),
            'context' => [
                'context' => 'matched_settlement',
            ],
        ];
    }

    public static function buildMessage(int $transType, int $transNo): string
    {
        return 'Transaction was MATCH settled ' . $transType . '::' . $transNo;
    }
}
