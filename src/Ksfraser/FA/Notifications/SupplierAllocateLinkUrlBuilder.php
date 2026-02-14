<?php

/**
 * Transaction Link Flow (UML Activity)
 *
 * @uml
 * start
 * :Caller (bank_import_controller/process_statements);
 * :SupplierAllocateLinkUrlBuilder::appRelativePath();  <<<< CURRENT FILE >>>>
 * :Controller buildFaAbsoluteUrl();
 * :TransactionLinkNotificationDisplayer::displayFromResultData();
 * :display_notification(anchor);
 * stop
 * @enduml
 *
 * Responsibility in flow:
 * - Build canonical app-relative URL for FA supplier allocation endpoint.
 */

declare(strict_types=1);

namespace Ksfraser\FA\Notifications;

/**
 * SRP: build supplier allocation URL paths for supplier payment transactions.
 */
final class SupplierAllocateLinkUrlBuilder
{
    public static function buildMessage(int $transType, int $transNo, int $supplierId): string
    {
        return 'Allocate Payment ' . $transType . '::' . $transNo . ' Supplier:' . $supplierId;
    }

    public static function appRelativePath(int $transType, int $transNo, int $supplierId): string
    {
        return 'purchasing/allocations/supplier_allocate.php?trans_type=' . $transType
            . '&trans_no=' . $transNo
            . '&supplier_id=' . $supplierId;
    }
}
