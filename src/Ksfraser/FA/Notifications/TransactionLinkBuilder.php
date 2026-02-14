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
 * :TransactionLinkBuilder::build();  <<<< CURRENT FILE >>>>
 * :TransactionLinkRoutePolicy::prioritize();
 * :TransactionLinkNotificationDisplayer::displayFromResultData();
 * if (mode == notification) then (yes)
 *   :display_notification() for each rendered link;
 * else (no)
 *   :return HTML to caller for page rendering;
 * endif
 * stop
 * @enduml
 *
 * Responsibility in flow:
 * - Normalize/extract links from result payload.
 * - Optionally derive fallback links from `trans_type` + `trans_no`.
 * - Delegate final ordering to `TransactionLinkRoutePolicy`.
 */

declare(strict_types=1);

namespace Ksfraser\FA\Notifications;

/**
 * SRP: build transaction link descriptors from result payload + transaction type.
 */
final class TransactionLinkBuilder
{
    /**
     * @var array<string, array{keys: string[], label: string}>
     */
    private $linkDefinitions;

    /** @var bool */
    private $enableDerivedLinks;

    /** @var TransactionLinkRoutePolicy */
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
        $this->linkDefinitions = $linkDefinitions ?? TransactionLinkNotificationDisplayer::defaultLinkDefinitions();
        $this->enableDerivedLinks = $enableDerivedLinks;
        $this->routePolicy = $routePolicy ?? new TransactionLinkRoutePolicy();
    }

    /**
     * @param array<string, mixed> $resultData
     * @return array<int, array{key: string, url: string, label: string, kind?: string}>
     */
    public function build(array $resultData, ?int $transType = null, array $context = []): array
    {
        $resolvedTransType = $transType;
        if ($resolvedTransType === null && isset($resultData['trans_type']) && is_numeric($resultData['trans_type'])) {
            $resolvedTransType = (int)$resultData['trans_type'];
        }

        $links = [];
        $seen = [];

        foreach ($this->extractStructuredLinks($resultData) as $structured) {
            $urlKey = strtolower(trim($structured['url']));
            if (isset($seen[$urlKey])) {
                continue;
            }
            $seen[$urlKey] = true;
            $links[] = $structured;
        }

        foreach ($this->linkDefinitions as $kind => $definition) {
            $url = $this->firstLinkForKeys($resultData, $definition['keys']);
            if ($url === null) {
                continue;
            }

            $urlKey = strtolower(trim($url));
            if (isset($seen[$urlKey])) {
                continue;
            }
            $seen[$urlKey] = true;

            $links[] = [
                'key' => $definition['keys'][0],
                'url' => $url,
                'label' => $definition['label'],
                'kind' => (string)$kind,
            ];
        }

        foreach ($this->deriveLinksFromTransactionType($resultData, $resolvedTransType) as $derived) {
            $urlKey = strtolower(trim($derived['url']));
            if (isset($seen[$urlKey])) {
                continue;
            }
            $seen[$urlKey] = true;
            $links[] = $derived;
        }

        return $this->routePolicy->prioritize($links, $resolvedTransType, $context);
    }

    /**
     * @param array<string, mixed> $resultData
     * @return array<int, array{key: string, url: string, label: string, kind?: string}>
     */
    private function extractStructuredLinks(array $resultData): array
    {
        if (!isset($resultData['links']) || !is_array($resultData['links'])) {
            return [];
        }

        $normalized = [];
        foreach ($resultData['links'] as $idx => $candidate) {
            if (!is_array($candidate)) {
                continue;
            }

            $url = isset($candidate['url']) && is_string($candidate['url']) ? trim($candidate['url']) : '';
            if ($url === '') {
                continue;
            }

            $label = isset($candidate['label']) && is_string($candidate['label']) && trim($candidate['label']) !== ''
                ? trim($candidate['label'])
                : 'View Link';

            $key = isset($candidate['key']) && is_string($candidate['key']) && trim($candidate['key']) !== ''
                ? trim($candidate['key'])
                : 'links_' . (string)$idx;

            $entry = [
                'key' => $key,
                'url' => $url,
                'label' => $label,
            ];

            if (isset($candidate['kind']) && is_string($candidate['kind']) && trim($candidate['kind']) !== '') {
                $entry['kind'] = strtolower(trim($candidate['kind']));
            }

            $normalized[] = $entry;
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $data
     * @param string[] $keys
     */
    private function firstLinkForKeys(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }

            $candidate = $data[$key];
            if (!is_string($candidate)) {
                continue;
            }

            $candidate = trim($candidate);
            if ($candidate === '') {
                continue;
            }

            return $candidate;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $resultData
     * @return array<int, array{key: string, url: string, label: string, kind?: string}>
     */
    private function deriveLinksFromTransactionType(array $resultData, ?int $transType): array
    {
        if (!$this->enableDerivedLinks) {
            return [];
        }

        if ($transType === null || !isset($resultData['trans_no']) || !is_numeric($resultData['trans_no'])) {
            return [];
        }

        $transNo = (int)$resultData['trans_no'];
        if ($transNo <= 0) {
            return [];
        }

        $links = [];

        $primaryMeta = $this->defaultPrimaryLinkMetaForTransType($transType);

        $links[] = [
            'key' => 'derived_view_link',
            'url' => "../../gl/view/gl_trans_view.php?type_id={$transType}&trans_no={$transNo}",
            'label' => $primaryMeta['label'],
            'kind' => $primaryMeta['kind'],
        ];

        foreach ($this->deriveNativeViewLinks($transType, $transNo) as $nativeLink) {
            $links[] = $nativeLink;
        }

        if ($this->isCustomerPaymentType($transType)) {
            $links[] = [
                'key' => 'derived_view_receipt_link',
                'url' => "../../sales/view/view_receipt.php?type_id={$transType}&trans_no={$transNo}",
                'label' => 'View Receipt',
                'kind' => 'receipt',
            ];
        }

        return $links;
    }

    /**
     * @return array<int, array{key: string, url: string, label: string, kind?: string}>
     */
    private function deriveNativeViewLinks(int $transType, int $transNo): array
    {
        switch ($transType) {
            case ST_SUPPAYMENT:
                return [[
                    'key' => 'derived_view_supplier_payment_link',
                    'url' => "../../purchasing/view/view_supp_payment.php?trans_no={$transNo}",
                    'label' => 'View Supplier Payment',
                    'kind' => 'payment',
                ]];

            case ST_BANKDEPOSIT:
                return [[
                    'key' => 'derived_view_bank_deposit_link',
                    'url' => "../../gl/view/gl_deposit_view.php?trans_no={$transNo}",
                    'label' => 'View Deposit',
                    'kind' => 'entry',
                ]];

            case ST_BANKPAYMENT:
                return [[
                    'key' => 'derived_view_bank_payment_link',
                    'url' => "../../gl/view/gl_payment_view.php?trans_no={$transNo}",
                    'label' => 'View Bank Payment',
                    'kind' => 'payment',
                ]];

            case ST_BANKTRANSFER:
                return [[
                    'key' => 'derived_view_bank_transfer_link',
                    'url' => "../../gl/view/bank_transfer_view.php?trans_no={$transNo}",
                    'label' => 'View Bank Transfer',
                    'kind' => 'entry',
                ]];

            default:
                return [];
        }
    }

    /**
     * @return array{label: string, kind: string}
     */
    private function defaultPrimaryLinkMetaForTransType(int $transType): array
    {
        switch ($transType) {
            case ST_JOURNAL:
                return ['label' => 'View Journal Entry', 'kind' => 'entry'];
            case ST_BANKPAYMENT:
                return ['label' => 'View Bank Payment', 'kind' => 'payment'];
            case ST_BANKDEPOSIT:
                return ['label' => 'View Deposit', 'kind' => 'entry'];
            case ST_BANKTRANSFER:
                return ['label' => 'View Bank Transfer', 'kind' => 'entry'];
            case ST_SALESINVOICE:
                return ['label' => 'View Sales Invoice', 'kind' => 'invoice'];
            case ST_CUSTCREDIT:
                return ['label' => 'View Customer Credit', 'kind' => 'invoice'];
            case ST_CUSTPAYMENT:
                return ['label' => 'View Customer Payment', 'kind' => 'payment'];
            case ST_CUSTDELIVERY:
                return ['label' => 'View Customer Delivery', 'kind' => 'entry'];
            case ST_LOCTRANSFER:
                return ['label' => 'View Location Transfer', 'kind' => 'entry'];
            case ST_INVADJUST:
                return ['label' => 'View Inventory Adjustment', 'kind' => 'entry'];
            case ST_PURCHORDER:
                return ['label' => 'View Purchase Order', 'kind' => 'invoice'];
            case ST_SUPPINVOICE:
                return ['label' => 'View Supplier Invoice', 'kind' => 'invoice'];
            case ST_SUPPCREDIT:
                return ['label' => 'View Supplier Credit', 'kind' => 'invoice'];
            case ST_SUPPAYMENT:
                return ['label' => 'View Supplier Payment', 'kind' => 'payment'];
            case ST_SUPPRECEIVE:
                return ['label' => 'View Supplier Receive', 'kind' => 'entry'];
            default:
                return ['label' => 'View Transaction', 'kind' => 'entry'];
        }
    }

    private function isCustomerPaymentType(int $transType): bool
    {
        return $transType === ST_CUSTPAYMENT;
    }
}
