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
 * :TransactionLinkRoutePolicy::prioritize();  <<<< CURRENT FILE >>>>
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
 * - Apply context/type-specific ordering preferences to already-built links.
 * - Keep sort behavior stable for equal-ranked links.
 */

declare(strict_types=1);

namespace Ksfraser\FA\Notifications;

/**
 * SRP: ordering policy for transaction links.
 *
 * This class does not create links; it only prioritizes already-built links.
 */
final class TransactionLinkRoutePolicy
{
    /**
     * @var array<string, string[]>
     */
    private $policyByContext;

    /**
     * @var array<int|string, string[]>
     */
    private $policyByTransType;

    /**
     * @param array<string, string[]>|null $policyByContext
     * @param array<int|string, string[]>|null $policyByTransType
     */
    public function __construct(?array $policyByContext = null, ?array $policyByTransType = null)
    {
        $this->policyByContext = $policyByContext ?? [];
        $this->policyByTransType = $policyByTransType ?? [];
    }

    /**
     * @param array<int, array{key: string, url: string, label: string, kind?: string}> $links
     * @param array<string, mixed> $context
     * @return array<int, array{key: string, url: string, label: string, kind?: string}>
     */
    public function prioritize(array $links, ?int $transType, array $context = []): array
    {
        $preferredKinds = $this->resolvePreferredKinds($transType, $context);
        if (empty($preferredKinds)) {
            return $links;
        }

        $rankMap = [];
        foreach ($preferredKinds as $idx => $kind) {
            $kind = strtolower(trim((string)$kind));
            if ($kind === '') {
                continue;
            }
            if (!isset($rankMap[$kind])) {
                $rankMap[$kind] = $idx;
            }
        }

        if (empty($rankMap)) {
            return $links;
        }

        $ranked = [];
        foreach ($links as $idx => $link) {
            $kind = $this->resolveKind($link);
            $rank = isset($rankMap[$kind]) ? (int)$rankMap[$kind] : 1000;
            $ranked[] = [
                'rank' => $rank,
                'idx' => $idx,
                'link' => $link,
            ];
        }

        usort($ranked, static function (array $a, array $b): int {
            if ($a['rank'] === $b['rank']) {
                return $a['idx'] <=> $b['idx'];
            }
            return $a['rank'] <=> $b['rank'];
        });

        $ordered = [];
        foreach ($ranked as $entry) {
            $ordered[] = $entry['link'];
        }

        return $ordered;
    }

    /**
     * @param array<string, mixed> $context
     * @return string[]
     */
    private function resolvePreferredKinds(?int $transType, array $context): array
    {
        if (isset($context['link_preferred_kinds']) && is_array($context['link_preferred_kinds'])) {
            return array_values(array_filter(array_map('strval', $context['link_preferred_kinds'])));
        }

        if (isset($context['context']) && is_string($context['context'])) {
            $ctx = strtolower(trim($context['context']));
            if ($ctx !== '' && isset($this->policyByContext[$ctx]) && is_array($this->policyByContext[$ctx])) {
                return $this->policyByContext[$ctx];
            }
        }

        if ($transType !== null) {
            if (isset($this->policyByTransType[$transType]) && is_array($this->policyByTransType[$transType])) {
                return $this->policyByTransType[$transType];
            }

            $key = (string)$transType;
            if (isset($this->policyByTransType[$key]) && is_array($this->policyByTransType[$key])) {
                return $this->policyByTransType[$key];
            }
        }

        return [];
    }

    /**
     * @param array{key: string, url: string, label: string, kind?: string} $link
     */
    private function resolveKind(array $link): string
    {
        if (isset($link['kind']) && is_string($link['kind']) && trim($link['kind']) !== '') {
            return strtolower(trim($link['kind']));
        }

        $key = strtolower((string)($link['key'] ?? ''));
        if (strpos($key, 'receipt') !== false) return 'receipt';
        if (strpos($key, 'invoice') !== false) return 'invoice';
        if (strpos($key, 'payment') !== false) return 'payment';
        if (strpos($key, 'edit') !== false) return 'edit';
        if (strpos($key, 'attach') !== false) return 'attachment';
        if (strpos($key, 'alloc') !== false) return 'allocate';
        return 'entry';
    }
}
