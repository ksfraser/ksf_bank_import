<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :PairedTransferDualSideAction [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for PairedTransferDualSideAction.
 */
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Actions;

use Ksfraser\HTML\Ajax\DivActivator;

/**
 * PairedTransferDualSideAction
 *
 * Single-responsibility action helper for the dual-side paired-transfer POST flow.
 *
 * Notes:
 * - Keeps action parsing and validation isolated from process_statements.php.
 * - Runtime execution can be wired later from the controller entry point as a one-liner.
 */
final class PairedTransferDualSideAction
{
    /** @var callable|null */
    private $processorFactory;

    /**
     * @param callable|null $processorFactory Factory returning object with processPairedTransfer(int): array
     */
    public function __construct(?callable $processorFactory = null)
    {
        $this->processorFactory = $processorFactory;
    }

    /**
     * Check whether request contains the dual-side action payload.
     *
     * @param array<string, mixed> $post
     */
    public function supports(array $post): bool
    {
        return isset($post['ProcessBothSides']) && is_array($post['ProcessBothSides']);
    }

    /**
     * Extract transaction id from ProcessBothSides payload.
     *
     * @param array<string, mixed> $post
     */
    public function extractTransactionId(array $post): ?int
    {
        if (!$this->supports($post)) {
            return null;
        }

        $key = array_key_first($post['ProcessBothSides']);
        if ($key === null || !is_scalar($key)) {
            return null;
        }

        $id = (int) $key;
        return $id > 0 ? $id : null;
    }

    /**
     * Return normalized action value for compatibility checks.
     *
     * @param array<string, mixed> $post
     */
    public function extractActionValue(array $post): ?string
    {
        if (!$this->supports($post)) {
            return null;
        }

        $key = array_key_first($post['ProcessBothSides']);
        if ($key === null) {
            return null;
        }

        $value = $post['ProcessBothSides'][$key] ?? null;
        return is_scalar($value) ? (string) $value : null;
    }

    /**
     * Execute paired transfer processing using extracted POST payload.
     *
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    public function execute(array $post): array
    {
        if (!$this->supports($post)) {
            return [
                'success' => false,
                'error' => 'ProcessBothSides action not present',
            ];
        }

        $transactionId = $this->extractTransactionId($post);
        if ($transactionId === null) {
            return [
                'success' => false,
                'error' => 'Missing or invalid ProcessBothSides transaction id',
            ];
        }

        try {
            $processor = $this->createProcessor();
            if (!is_object($processor) || !method_exists($processor, 'processPairedTransfer')) {
                throw new \RuntimeException('Paired transfer processor is not available');
            }

            /** @var array<string, mixed> $result */
            $result = $processor->processPairedTransfer($transactionId);

            if (!isset($result['success'])) {
                $result['success'] = true;
            }

            return $result;
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Execute and emit legacy UI notifications when FA functions are available.
     *
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    public function dispatchToUi(array $post): array
    {
        $result = $this->execute($post);

        if (!class_exists(DivActivator::class)) {
            $candidate = dirname(__DIR__, 3) . '/HTML/Ajax/DivActivator.php';
            if (is_file($candidate)) {
                require_once $candidate;
            }
        }

        if (class_exists(DivActivator::class)) {
            DivActivator::activateDocTable();
        }

        if (!empty($result['success'])) {
            if (function_exists('display_notification')) {
                if (isset($result['trans_no']) && isset($result['trans_type']) && function_exists('get_gl_view_str')) {
                    display_notification(get_gl_view_str((int) $result['trans_type'], (int) $result['trans_no'], _('View New Transfer')));
                }

                $message = isset($result['message']) ? (string) $result['message'] : 'Paired transfer processed.';
                display_notification($message);
            }
        } else {
            if (function_exists('display_error')) {
                display_error((string) ($result['error'] ?? 'Failed to process paired transfer'));
            }
        }

        return $result;
    }

    /**
     * @return object
     */
    private function createProcessor()
    {
        if ($this->processorFactory !== null) {
            return ($this->processorFactory)();
        }

        if (!class_exists('\KsfBankImport\\Services\\PairedTransferProcessor')) {
            $candidate = dirname(__DIR__, 4) . '/Services/PairedTransferProcessor.php';
            if (is_file($candidate)) {
                require_once $candidate;
            }
        }

        if (!class_exists('\KsfBankImport\\Services\\PairedTransferProcessor')) {
            throw new \RuntimeException('PairedTransferProcessor class not found');
        }

        return new \KsfBankImport\Services\PairedTransferProcessor();
    }
}
