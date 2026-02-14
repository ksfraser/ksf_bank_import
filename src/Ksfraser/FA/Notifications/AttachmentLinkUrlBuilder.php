<?php

/**
 * Transaction Link Flow (UML Activity)
 *
 * @uml
 * start
 * :Caller (bank_import_controller/process_statements);
 * :AttachmentLinkUrlBuilder::appRelativePath();  <<<< CURRENT FILE >>>>
 * :Controller buildFaAbsoluteUrl();
 * :TransactionLinkNotificationDisplayer::displayFromResultData();
 * :display_notification(anchor);
 * stop
 * @enduml
 *
 * Responsibility in flow:
 * - Build canonical app-relative URL for FA attachment endpoint.
 */

declare(strict_types=1);

namespace Ksfraser\FA\Notifications;

/**
 * SRP: build attachment URL paths for FA transaction documents.
 */
final class AttachmentLinkUrlBuilder
{
    public static function buildMessage(int $transType, int $transNo): string
    {
        return 'Attach Document ' . $transType . '::' . $transNo;
    }

    public static function appRelativePath(int $transType, int $transNo): string
    {
        return 'admin/attachments.php?filterType=' . $transType . '&trans_no=' . $transNo;
    }
}
