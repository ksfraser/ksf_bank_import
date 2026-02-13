<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Actions\PairedTransferDualSideAction;

require_once __DIR__ . '/../../src/Ksfraser/FaBankImport/Actions/PairedTransferDualSideAction.php';

final class PairedTransferDualSideActionTest extends TestCase
{
    public function testSupportsReturnsTrueForValidPayload(): void
    {
        $action = new PairedTransferDualSideAction();

        $post = [
            'ProcessBothSides' => ['123' => 'process'],
        ];

        $this->assertTrue($action->supports($post));
    }

    public function testSupportsReturnsFalseWhenMissing(): void
    {
        $action = new PairedTransferDualSideAction();

        $this->assertFalse($action->supports([]));
    }

    public function testExtractTransactionIdFromPayload(): void
    {
        $action = new PairedTransferDualSideAction();

        $post = [
            'ProcessBothSides' => ['456' => 'process'],
        ];

        $this->assertSame(456, $action->extractTransactionId($post));
    }

    public function testExtractTransactionIdReturnsNullForEmptyPayload(): void
    {
        $action = new PairedTransferDualSideAction();

        $post = [
            'ProcessBothSides' => [],
        ];

        $this->assertNull($action->extractTransactionId($post));
    }

    public function testExtractActionValueReturnsNormalizedString(): void
    {
        $action = new PairedTransferDualSideAction();

        $post = [
            'ProcessBothSides' => ['789' => 'process'],
        ];

        $this->assertSame('process', $action->extractActionValue($post));
    }

    public function testExecuteReturnsErrorWhenActionMissing(): void
    {
        $action = new PairedTransferDualSideAction();

        $result = $action->execute([]);

        $this->assertFalse($result['success']);
        $this->assertSame('ProcessBothSides action not present', $result['error']);
    }

    public function testExecuteReturnsErrorWhenTransactionIdInvalid(): void
    {
        $action = new PairedTransferDualSideAction();

        $result = $action->execute(['ProcessBothSides' => ['x' => 'process']]);

        $this->assertFalse($result['success']);
        $this->assertSame('Missing or invalid ProcessBothSides transaction id', $result['error']);
    }

    public function testExecuteUsesInjectedProcessorAndReturnsSuccessPayload(): void
    {
        $processor = new class {
            public function processPairedTransfer($transactionId): array
            {
                return [
                    'success' => true,
                    'trans_no' => 1234,
                    'trans_type' => 4,
                    'message' => 'OK',
                ];
            }
        };

        $action = new PairedTransferDualSideAction(static function () use ($processor) {
            return $processor;
        });

        $result = $action->execute(['ProcessBothSides' => ['123' => 'process']]);

        $this->assertTrue($result['success']);
        $this->assertSame(1234, $result['trans_no']);
        $this->assertSame(4, $result['trans_type']);
    }

    public function testExecuteReturnsErrorWhenProcessorThrows(): void
    {
        $processor = new class {
            public function processPairedTransfer($transactionId): array
            {
                throw new \RuntimeException('boom');
            }
        };

        $action = new PairedTransferDualSideAction(static function () use ($processor) {
            return $processor;
        });

        $result = $action->execute(['ProcessBothSides' => ['123' => 'process']]);

        $this->assertFalse($result['success']);
        $this->assertSame('boom', $result['error']);
    }
}
