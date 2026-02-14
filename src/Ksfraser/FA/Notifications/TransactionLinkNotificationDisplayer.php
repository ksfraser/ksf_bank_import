<?php

/**
 * Transaction Link Flow (UML Activity)
 *
 * @uml
 * start
 * :Caller (process_statements / bank_import_controller);
 * if (Legacy namespace reference?) then (yes)
 *   :FaBankImport\\Notifications alias shim;
 * endif
 * :TransactionResultLinkPresenter::displayFromResult();
 * :extract link data + resolve output mode;
 * :TransactionLinkBuilder::build();
 * :TransactionLinkRoutePolicy::prioritize();
 * :TransactionLinkNotificationDisplayer::displayFromResultData();  <<<< CURRENT FILE >>>>
 * if (mode == notification) then (yes)
 *   :display_notification() for each rendered link;
 * else (no)
 *   :return HTML to caller for page rendering;
 * endif
 * stop
 * @enduml
 *
 * Responsibility in flow:
 * - Convert prioritized link descriptors into HTML anchors.
 * - Send links through FA notifications or return page HTML, based on mode.
 */

declare(strict_types=1);

namespace Ksfraser\FA\Notifications;

use Ksfraser\HTML\Elements\HtmlA;

/**
 * SRP: render transaction navigation links as FA notifications.
 *
 * Reusable across modules by passing handler/result data arrays.
 */
final class TransactionLinkNotificationDisplayer
{
    public const MODE_NOTIFICATION = 'notification';
    public const MODE_HTML = 'html';

    /**
     * @var array<string, array{keys: string[], label: string}>
     */
    private $linkDefinitions;

    /**
     * @var TransactionLinkBuilder
     */
    private $linkBuilder;

    /**
     * @var TransactionLinkRoutePolicy
     */
    private $routePolicy;

    /**
     * @param array<string, array{keys: string[], label: string}>|null $linkDefinitions
     */
    public function __construct(
        ?array $linkDefinitions = null,
        bool $enableDerivedLinks = false,
        ?TransactionLinkRoutePolicy $routePolicy = null
    )
    {
        $this->linkDefinitions = $linkDefinitions ?? self::defaultLinkDefinitions();
        $this->routePolicy = $routePolicy ?? new TransactionLinkRoutePolicy();
        $this->linkBuilder = new TransactionLinkBuilder($this->linkDefinitions, $enableDerivedLinks, $this->routePolicy);
    }

    /**
     * @return array<string, array{keys: string[], label: string}>
     */
    public static function defaultLinkDefinitions(): array
    {
        return [
            'receipt' => [
                'keys' => ['view_receipt_link', 'receipt_link'],
                'label' => 'View Receipt',
            ],
            'entry' => [
                'keys' => ['view_gl_link', 'view_entry_link', 'view_link', 'gl_link'],
                'label' => 'View Entry',
            ],
            'payment' => [
                'keys' => ['view_payment_link', 'view_supplier_payment_link', 'view_customer_payment_link', 'payment_link'],
                'label' => 'View Payment',
            ],
            'invoice' => [
                'keys' => ['view_invoice_link', 'invoice_link', 'view_sales_invoice_link'],
                'label' => 'View Invoice',
            ],
            'edit' => [
                'keys' => ['edit_link', 'edit_entry_link', 'edit_receipt_link', 'edit_payment_link', 'edit_invoice_link'],
                'label' => 'Edit Transaction',
            ],
            'attachment' => [
                'keys' => ['attach_document_link', 'attach_link', 'attachment_link'],
                'label' => 'Attach Document',
            ],
            'allocate' => [
                'keys' => ['allocate_link'],
                'label' => 'Allocate Payment',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $resultData
     */
    public function displayFromResultData(
        array $resultData,
        ?int $transType = null,
        string $mode = self::MODE_NOTIFICATION,
        array $context = []
    ): string
    {
        $links = $this->linkBuilder->build($resultData, $transType, $context);
        if (empty($links)) {
            return '';
        }

        $htmlParts = [];
        foreach ($links as $link) {
            $htmlParts[] = $this->buildLinkHtml($link['url'], $link['label']);
        }

        $joinedHtml = implode("\n", $htmlParts);

        if ($mode === self::MODE_NOTIFICATION) {
            if (!function_exists('display_notification')) {
                return $joinedHtml;
            }

            foreach ($htmlParts as $html) {
                display_notification($html);
            }
        }

        return $joinedHtml;
    }

    private function buildLinkHtml(string $url, string $label): string
    {
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
