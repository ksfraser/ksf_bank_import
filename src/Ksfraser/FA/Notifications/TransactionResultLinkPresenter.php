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
 * :TransactionResultLinkPresenter::displayFromResult();  <<<< CURRENT FILE >>>>
 * :extract link data + resolve output mode;
 * :TransactionLinkBuilder::build();
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
 * - Orchestrate result extraction, policy wiring, and rendering mode.
 * - Bridge `process_statements` result payloads to reusable notification classes.
 */

declare(strict_types=1);

namespace Ksfraser\FA\Notifications;

/**
 * SRP: extract and render transaction result links for process_statements flow.
 */
final class TransactionResultLinkPresenter
{
    /**
     * @param mixed $result TransactionResult-like object or legacy array
     * @param array<string, mixed> $config
     */
    public function displayFromResult($result, array $config = [], string $partnerType = ''): string
    {
        $linkData = $this->extractLinkData($result);
        if (empty($linkData)) {
            return '';
        }

        $deriveFallbackLinks = (bool)($config['transaction_link_derive_fallback'] ?? false);

        $routePolicy = null;
        if (class_exists('\\Ksfraser\\FA\\Notifications\\TransactionLinkRoutePolicy')) {
            $policyByContext = is_array($config['transaction_link_policy_by_context'] ?? null)
                ? $config['transaction_link_policy_by_context']
                : [];
            $policyByTransType = is_array($config['transaction_link_policy_by_trans_type'] ?? null)
                ? $config['transaction_link_policy_by_trans_type']
                : [];

            $routePolicy = new TransactionLinkRoutePolicy($policyByContext, $policyByTransType);
        }

        $linkDisplayer = new TransactionLinkNotificationDisplayer(null, $deriveFallbackLinks, $routePolicy);
        $linkOutputMode = $this->resolveOutputMode($config);
        $resultTransType = $this->extractTransType($result, $linkData);

        $linkContext = [
            'context' => 'process_statements',
            'partner_type' => $partnerType,
        ];
        if (isset($config['transaction_link_preferred_kinds']) && is_array($config['transaction_link_preferred_kinds'])) {
            $linkContext['link_preferred_kinds'] = $config['transaction_link_preferred_kinds'];
        }

        $renderedLinksHtml = $linkDisplayer->displayFromResultData(
            $linkData,
            $resultTransType,
            $linkOutputMode,
            $linkContext
        );

        if (
            $linkOutputMode === TransactionLinkNotificationDisplayer::MODE_HTML
            && $renderedLinksHtml !== ''
            && function_exists('display_notification')
        ) {
            display_notification($renderedLinksHtml);
        }

        return $renderedLinksHtml;
    }

    /**
     * @param mixed $result
     * @return array<string, mixed>
     */
    private function extractLinkData($result): array
    {
        if (is_object($result) && method_exists($result, 'getData')) {
            $allData = $result->getData();
            if (is_array($allData)) {
                return $allData;
            }
        }

        if (is_array($result)) {
            return $result;
        }

        return [];
    }

    /**
     * @param array<string, mixed> $config
     */
    private function resolveOutputMode(array $config): string
    {
        $requestedMode = strtolower(trim((string)($config['transaction_link_output_mode'] ?? '')));
        if (in_array($requestedMode, [
            TransactionLinkNotificationDisplayer::MODE_NOTIFICATION,
            TransactionLinkNotificationDisplayer::MODE_HTML,
        ], true)) {
            return $requestedMode;
        }

        return TransactionLinkNotificationDisplayer::MODE_NOTIFICATION;
    }

    /**
     * @param mixed $result
     * @param array<string, mixed> $linkData
     */
    private function extractTransType($result, array $linkData): ?int
    {
        if (is_object($result) && method_exists($result, 'getTransType')) {
            return (int)$result->getTransType();
        }

        if (isset($linkData['trans_type']) && is_numeric($linkData['trans_type'])) {
            return (int)$linkData['trans_type'];
        }

        return null;
    }
}
