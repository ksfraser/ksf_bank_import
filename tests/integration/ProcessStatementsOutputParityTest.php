<?php

use PHPUnit\Framework\TestCase;

/**
 * Output parity tests for process_statements.php across key snapshots.
 *
 * Focuses on observable behavior with mocks:
 * - POST action dispatch outputs (controller method calls / notifications)
 * - ProcessTransaction validation outputs (error + Ajax activation)
 */
class ProcessStatementsOutputParityTest extends TestCase
{
    private const APRIL_2025_COMMIT = '2d8f2a7';
    private const NOV_2025_COMMIT = '524664f';
    private const BASELINED_COMMIT = 'b56210c';

    public static $fx = [
        'errors' => [],
        'notifications' => [],
        'ajax' => [],
        'renderConstructed' => [],
        'renderDisplayed' => [],
        'getTransactionsArgs' => [],
        'bankAccountByNumber' => [],
        'transaction' => [
            'our_account' => 'ACC-001',
            'transactionAmount' => 100.00,
            'transactionDC' => 'D',
            'valueTimestamp' => '2025-04-09',
            'transactionTitle' => 'Parity test',
        ],
    ];

    private function repoRoot(): string
    {
        $root = realpath(__DIR__ . '/../../');
        $this->assertNotFalse($root);
        return $root;
    }

    private function loadCurrentSource(): string
    {
        $path = $this->repoRoot() . DIRECTORY_SEPARATOR . 'process_statements.php';
        $this->assertFileExists($path);
        $content = file_get_contents($path);
        $this->assertNotFalse($content);
        return $content;
    }

    private function loadSourceFromCommit(string $commitHash): string
    {
        $root = $this->repoRoot();
        $cmd = sprintf(
            'git -C %s show %s:%s 2>NUL',
            escapeshellarg($root),
            escapeshellarg($commitHash),
            escapeshellarg('process_statements.php')
        );

        $content = shell_exec($cmd);
        if (!is_string($content) || $content === '') {
            $this->markTestSkipped("Could not load process_statements.php from commit {$commitHash}");
        }

        return $content;
    }

    private function extractCurlyBlockByRegexStart(string $source, string $startRegex): string
    {
        if (!preg_match($startRegex, $source, $m, PREG_OFFSET_CAPTURE)) {
            $this->fail('Unable to find block start pattern: ' . $startRegex);
        }

        $startPos = $m[0][1];
        $openPos = strpos($source, '{', $startPos);
        $this->assertNotFalse($openPos, 'Expected opening brace for block');

        $depth = 0;
        $len = strlen($source);
        for ($i = $openPos; $i < $len; $i++) {
            $ch = $source[$i];
            if ($ch === '{') {
                $depth++;
            } elseif ($ch === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($source, $startPos, $i - $startPos + 1);
                }
            }
        }

        $this->fail('Could not close brace-matched block');
        return '';
    }

    private function extractActionHandlersBlock(string $source): string
    {
        $start = strpos($source, 'unset($k, $v);');
        $endMarker = '/*-------------------Process Transaction';
        $end = strpos($source, $endMarker);

        $this->assertNotFalse($start, 'Could not find action handlers start');
        $this->assertNotFalse($end, 'Could not find action handlers end marker');
        $this->assertTrue($end > $start, 'Invalid action handlers boundaries');

        return substr($source, $start, $end - $start);
    }

    private function extractProcessTransactionBlock(string $source): string
    {
        if (!preg_match("/if\\s*\\(\\s*isset\\s*\\(\\s*\\\$_POST\\['ProcessTransaction'\\]\\s*\\)\\s*\\)\\s*\\{/", $source, $m, PREG_OFFSET_CAPTURE)) {
            $this->fail('Could not find ProcessTransaction if-block start');
        }

        $start = $m[0][1];
        $endMarker = '/*----------------------------------------------------------------------------------------------*/';
        $end = strpos($source, $endMarker, $start);

        $this->assertNotFalse($end, 'Could not find ProcessTransaction block end marker');
        $this->assertTrue($end > $start, 'Invalid ProcessTransaction block boundaries');

        return substr($source, $start, $end - $start);
    }

    private function extractRenderLoopBlock(string $source): string
    {
        if (!preg_match('/foreach\s*\(\s*\$trzs\s+as\s+\$trz_code\s*=>\s*\$trz_data\s*\)/', $source, $m, PREG_OFFSET_CAPTURE)) {
            $this->fail('Could not find transaction rendering foreach block');
        }

        $start = $m[0][1];
        $tail = substr($source, $start);

        if (!preg_match('/end_table\s*\(/', $tail, $mEnd, PREG_OFFSET_CAPTURE)) {
            $this->fail('Could not find render loop end boundary (end_table)');
        }

        $end = $start + $mEnd[0][1];
        return substr($source, $start, $end - $start);
    }

    private function extractStatusFilterBlock(string $source): string
    {
        if (!preg_match("/if\\s*\\(\\s*\\\$_POST\\['statusFilter'\\]\\s*==\\s*0\\s*OR\\s*\\\$_POST\\['statusFilter'\\]\\s*==\\s*1\\s*\\)/", $source, $m, PREG_OFFSET_CAPTURE)) {
            $this->fail('Could not find statusFilter branch block');
        }

        $start = $m[0][1];
        $tail = substr($source, $start);

        if (!preg_match('/start_table\s*\(/', $tail, $mEnd, PREG_OFFSET_CAPTURE)) {
            $this->fail('Could not find statusFilter block end boundary (start_table)');
        }

        $end = $start + $mEnd[0][1];
        return substr($source, $start, $end - $start);
    }

    private function runActionHandlersBlock(string $block, array $post): array
    {
        self::$fx['errors'] = [];
        self::$fx['notifications'] = [];
        self::$fx['ajax'] = [];

        $_POST = $post;

        $bi_controller = new ProcessStatementsOutputParity_ControllerSpy();
        $Ajax = new ProcessStatementsOutputParity_AjaxSpy();

        $evalBlock = $this->prepareBlockForHarness($block);
        eval($evalBlock);

        return [
            'controllerCalls' => $bi_controller->calls,
            'notifications' => self::$fx['notifications'],
            'errors' => self::$fx['errors'],
            'ajax' => self::$fx['ajax'],
        ];
    }

    private function runProcessTransactionValidationBlock(string $ifBlock, array $post, array $bankAccountByNumber): array
    {
        self::$fx['errors'] = [];
        self::$fx['notifications'] = [];
        self::$fx['ajax'] = [];
        self::$fx['bankAccountByNumber'] = $bankAccountByNumber;

        $_POST = $post;

        $bi_controller = new ProcessStatementsOutputParity_ControllerSpy();
        $Ajax = new ProcessStatementsOutputParity_AjaxSpy();

        $evalBlock = $this->prepareBlockForHarness($ifBlock);
        eval($evalBlock);

        return [
            'controllerCalls' => $bi_controller->calls,
            'notifications' => self::$fx['notifications'],
            'errors' => self::$fx['errors'],
            'ajax' => self::$fx['ajax'],
        ];
    }

    private function prepareBlockForHarness(string $block): string
    {
        $prepared = str_replace('new bi_transactions_model()', 'new ProcessStatementsOutputParity_BiTransactionsModelStub()', $block);
        $prepared = preg_replace('/\bdisplay_error\s*\(/', 'ProcessStatementsOutputParity_capture_error(', $prepared);
        $prepared = preg_replace('/\bdisplay_notification\s*\(/', 'ProcessStatementsOutputParity_capture_notification(', $prepared);
        return $prepared;
    }

    private function runRenderLoopBlock(string $block, array $trzs): array
    {
        self::$fx['renderConstructed'] = [];
        self::$fx['renderDisplayed'] = [];

        $vendor_list = [];
        $optypes = [
            'SP' => 'Supplier',
            'CU' => 'Customer',
            'QE' => 'Quick Entry',
            'BT' => 'Bank Transfer',
            'MA' => 'Manual settlement',
            'ZZ' => 'Matched',
        ];

        $prepared = str_replace("require_once(__DIR__ . '/class.bi_lineitem.php');", '', $block);
        $prepared = str_replace('new bi_lineitem(', 'new ProcessStatementsOutputParity_LineItemStub(', $prepared);

        eval($prepared);

        return [
            'constructed' => self::$fx['renderConstructed'],
            'displayed' => self::$fx['renderDisplayed'],
        ];
    }

    private function runStatusFilterBlock(string $block, $statusFilter): array
    {
        self::$fx['getTransactionsArgs'] = [];

        $_POST['statusFilter'] = $statusFilter;
        $trzs = [];
        $bit = new ProcessStatementsOutputParity_BiTransactionsModelStub();

        $prepared = str_replace('new bi_transactions_model()', 'new ProcessStatementsOutputParity_BiTransactionsModelStub()', $block);
        eval($prepared);

        return self::$fx['getTransactionsArgs'];
    }

    private function isControllerDispatchVariant(string $processTransactionBlock): bool
    {
        return
            strpos($processTransactionBlock, '$bi_controller->processSupplierTransaction();') !== false
            && strpos($processTransactionBlock, '$bi_controller->processCustomerPayment();') !== false
            && substr_count($processTransactionBlock, '$bi_controller->processTransactions();') >= 4;
    }

    public function testOutputParity_PostActionDispatch_AcrossSnapshots(): void
    {
        $sources = [
            'april' => $this->loadSourceFromCommit(self::APRIL_2025_COMMIT),
            'november' => $this->loadSourceFromCommit(self::NOV_2025_COMMIT),
            'baselined' => $this->loadSourceFromCommit(self::BASELINED_COMMIT),
            'restored' => $this->loadCurrentSource(),
        ];

        $actions = [
            'UnsetTrans' => 'unsetTrans',
            'AddCustomer' => 'addCustomer',
            'AddVendor' => 'addVendor',
            'ToggleTransaction' => 'toggleDebitCredit',
        ];

        foreach ($actions as $postAction => $expectedCall) {
            $reference = null;

            foreach ($sources as $label => $source) {
                $block = $this->extractActionHandlersBlock($source);
                $result = $this->runActionHandlersBlock($block, [$postAction => [1 => 'x']]);

                $this->assertContains($expectedCall, $result['controllerCalls'], "{$label}: expected {$expectedCall} call");

                $signature = [
                    'calls' => $result['controllerCalls'],
                    'errors' => $result['errors'],
                    'ajax' => $result['ajax'],
                ];

                if ($reference === null) {
                    $reference = $signature;
                } else {
                    $this->assertSame($reference, $signature, "{$label}: action '{$postAction}' parity mismatch");
                }
            }
        }
    }

    public function testOutputParity_ProcessTransactionMissingPartnerId_AcrossSnapshots(): void
    {
        $sources = [
            'april' => $this->loadSourceFromCommit(self::APRIL_2025_COMMIT),
            'november' => $this->loadSourceFromCommit(self::NOV_2025_COMMIT),
            'baselined' => $this->loadSourceFromCommit(self::BASELINED_COMMIT),
            'restored' => $this->loadCurrentSource(),
        ];

        $post = [
            'ProcessTransaction' => [42 => 'Process'],
            'partnerType' => [42 => 'SP'],
            // partnerId_42 intentionally missing
        ];

        $reference = null;
        $tested = 0;

        foreach ($sources as $label => $source) {
            $ifBlock = $this->extractProcessTransactionBlock($source);

            if (strpos($ifBlock, 'missing partnerId') === false) {
                // Some snapshots route through alternate wrappers. Skip this specific parity assertion there.
                continue;
            }

            $tested++;

            $result = $this->runProcessTransactionValidationBlock($ifBlock, $post, ['ACC-001' => ['id' => 1]]);

            $this->assertContains('missing partnerId', $result['errors'], "{$label}: missing partnerId error expected");
            $this->assertContains('doc_tbl', $result['ajax'], "{$label}: Ajax activate doc_tbl expected");

            $signature = [
                'errors' => $result['errors'],
                'ajax' => $result['ajax'],
            ];

            if ($reference === null) {
                $reference = $signature;
            } else {
                $this->assertSame($reference, $signature, "{$label}: missing-partnerId output parity mismatch");
            }
        }

        $this->assertGreaterThanOrEqual(2, $tested, 'Expected to validate missing-partnerId parity on at least two snapshots.');
    }

    public function testOutputParity_ProcessTransactionInvalidBankAccount_AcrossSnapshots(): void
    {
        $sources = [
            'april' => $this->loadSourceFromCommit(self::APRIL_2025_COMMIT),
            'november' => $this->loadSourceFromCommit(self::NOV_2025_COMMIT),
            'baselined' => $this->loadSourceFromCommit(self::BASELINED_COMMIT),
            'restored' => $this->loadCurrentSource(),
        ];

        $post = [
            'ProcessTransaction' => [77 => 'Process'],
            'partnerType' => [77 => 'SP'],
            'partnerId_77' => '9001',
            'cids' => [77 => ''],
        ];

        foreach ($sources as $label => $source) {
            $ifBlock = $this->extractProcessTransactionBlock($source);

            $result = $this->runProcessTransactionValidationBlock($ifBlock, $post, []);

            $this->assertNotEmpty($result['errors'], "{$label}: expected invalid bank account error output");
            $this->assertContains('doc_tbl', $result['ajax'], "{$label}: Ajax activate doc_tbl expected");
            $this->assertStringContainsString('the bank account', implode(' ', $result['errors']), "{$label}: expected bank account error text");
        }
    }

    public function testOutputParity_ProcessTransactionAllPartnerTypes_AcrossSnapshots(): void
    {
        // Full 6-path partner-type equivalence is only directly comparable on
        // controller-dispatch variants (baseline-restored timeline).
        $sources = [
            'baselined' => $this->loadSourceFromCommit(self::BASELINED_COMMIT),
            'restored' => $this->loadCurrentSource(),
        ];

        $partnerTypes = ['SP', 'CU', 'QE', 'BT', 'MA', 'ZZ'];

        foreach ($partnerTypes as $partnerType) {
            $reference = null;
            $tested = 0;

            foreach ($sources as $label => $source) {
                $ifBlock = $this->extractProcessTransactionBlock($source);

                // Only compare snapshots on the controller-dispatch variant.
                if (!$this->isControllerDispatchVariant($ifBlock)) {
                    continue;
                }

                $tested++;

                $post = [
                    'ProcessTransaction' => [88 => 'Process'],
                    'partnerType' => [88 => $partnerType],
                    'partnerId_88' => '1001',
                    'cids' => [88 => ''],
                ];

                $result = $this->runProcessTransactionValidationBlock($ifBlock, $post, ['ACC-001' => ['id' => 1]]);

                $normalized = [
                    'hasErrors' => !empty($result['errors']),
                    'hasDocTblActivation' => in_array('doc_tbl', $result['ajax'], true),
                ];

                if ($reference === null) {
                    $reference = $normalized;
                } else {
                    $this->assertSame($reference, $normalized, "{$label}: partner type {$partnerType} parity mismatch");
                }
            }

            $this->assertSame(2, $tested, "Expected baseline and restored comparables for partner type {$partnerType}");
        }
    }

    public function testOutputParity_ProcessTransactionAllPartnerTypes_DispatchCallsBaselineVsRestored(): void
    {
        $baselineSource = $this->loadSourceFromCommit(self::BASELINED_COMMIT);
        $restoredSource = $this->loadCurrentSource();

        $expectedByType = [
            'SP' => 'processSupplierTransaction',
            'CU' => 'processCustomerPayment',
            'QE' => 'processTransactions',
            'BT' => 'processTransactions',
            'MA' => 'processTransactions',
            'ZZ' => 'processTransactions',
        ];

        foreach ($expectedByType as $partnerType => $expectedCall) {
            $post = [
                'ProcessTransaction' => [101 => 'Process'],
                'partnerType' => [101 => $partnerType],
                'partnerId_101' => '1001',
                'cids' => [101 => ''],
            ];

            $baselineBlock = $this->extractProcessTransactionBlock($baselineSource);
            $this->assertTrue($this->isControllerDispatchVariant($baselineBlock), 'baselined: expected controller-dispatch variant');
            $baselineResult = $this->runProcessTransactionValidationBlock($baselineBlock, $post, ['ACC-001' => ['id' => 1]]);
            $baselineCalls = array_values(array_filter($baselineResult['controllerCalls'], function ($c) {
                return in_array($c, ['processSupplierTransaction', 'processCustomerPayment', 'processTransactions'], true);
            }));
            $this->assertContains($expectedCall, $baselineCalls, "baselined: expected {$expectedCall} dispatch for {$partnerType}");

            $restoredBlock = $this->extractProcessTransactionBlock($restoredSource);
            $this->assertTrue($this->isControllerDispatchVariant($restoredBlock), 'restored: expected controller-dispatch fallback variant present in source');
            $restoredResult = $this->runProcessTransactionValidationBlock($restoredBlock, $post, ['ACC-001' => ['id' => 1]]);
            $restoredCalls = array_values(array_filter($restoredResult['controllerCalls'], function ($c) {
                return in_array($c, ['processSupplierTransaction', 'processCustomerPayment', 'processTransactions'], true);
            }));

            // Restored code may route via recovered TransactionProcessor enhancement (no controller dispatch),
            // or via legacy fallback switch (controller dispatch). Accept either path if behavior remains clean.
            if (!empty($restoredCalls)) {
                $this->assertContains($expectedCall, $restoredCalls, "restored: expected {$expectedCall} dispatch for {$partnerType}");
            } else {
                $this->assertEmpty($restoredResult['errors'], "restored: strategy path should not emit errors for {$partnerType}");
                $this->assertContains('doc_tbl', $restoredResult['ajax'], "restored: strategy path should still activate doc_tbl for {$partnerType}");
            }
        }
    }

    public function testOutputParity_StatusFilterBranchBaselineVsRestored(): void
    {
        $sources = [
            'baselined' => $this->loadSourceFromCommit(self::BASELINED_COMMIT),
            'restored' => $this->loadCurrentSource(),
        ];

        $cases = [
            0 => [0],
            1 => [1],
            2 => [null],
        ];

        foreach ($cases as $status => $expectedArgs) {
            $reference = null;

            foreach ($sources as $label => $source) {
                $block = $this->extractStatusFilterBlock($source);
                $args = $this->runStatusFilterBlock($block, $status);

                $this->assertSame($expectedArgs, $args, "{$label}: statusFilter={$status} branch output mismatch");

                if ($reference === null) {
                    $reference = $args;
                } else {
                    $this->assertSame($reference, $args, "{$label}: statusFilter={$status} parity mismatch");
                }
            }
        }
    }

    public function testOutputParity_ProcessTransactionUnknownPartnerType_DefaultBranchBaselineVsRestored(): void
    {
        $sources = [
            'baselined' => $this->loadSourceFromCommit(self::BASELINED_COMMIT),
            'restored' => $this->loadCurrentSource(),
        ];

        $reference = null;

        foreach ($sources as $label => $source) {
            $ifBlock = $this->extractProcessTransactionBlock($source);
            $this->assertTrue($this->isControllerDispatchVariant($ifBlock), "{$label}: expected controller-dispatch variant");

            $post = [
                'ProcessTransaction' => [202 => 'Process'],
                'partnerType' => [202 => 'XX'],
                'partnerId_202' => '1001',
                'cids' => [202 => ''],
            ];

            $result = $this->runProcessTransactionValidationBlock($ifBlock, $post, ['ACC-001' => ['id' => 1]]);

            $actualCalls = array_values(array_filter($result['controllerCalls'], function ($c) {
                return in_array($c, ['processSupplierTransaction', 'processCustomerPayment', 'processTransactions'], true);
            }));

            $signature = [
                'dispatchCalls' => $actualCalls,
                'hasDocTblActivation' => in_array('doc_tbl', $result['ajax'], true),
                'hasErrors' => !empty($result['errors']),
            ];

            if ($reference === null) {
                $reference = $signature;
            } else {
                $this->assertSame($reference, $signature, "{$label}: unknown partnerType default-branch parity mismatch");
            }
        }

        $this->assertSame([], $reference['dispatchCalls']);
        $this->assertTrue($reference['hasDocTblActivation']);
        $this->assertFalse($reference['hasErrors']);
    }

    public function testOutputParity_ProcessTransactionMissingPartnerTypeGuardBaselineVsRestored(): void
    {
        $sources = [
            'baselined' => $this->loadSourceFromCommit(self::BASELINED_COMMIT),
            'restored' => $this->loadCurrentSource(),
        ];

        $reference = null;

        foreach ($sources as $label => $source) {
            $ifBlock = $this->extractProcessTransactionBlock($source);
            $this->assertTrue($this->isControllerDispatchVariant($ifBlock), "{$label}: expected controller-dispatch variant");

            $post = [
                'ProcessTransaction' => [303 => 'Process'],
                // partnerType[303] intentionally missing
                'partnerId_303' => '1001',
                'cids' => [303 => ''],
            ];

            $result = $this->runProcessTransactionValidationBlock($ifBlock, $post, ['ACC-001' => ['id' => 1]]);

            $signature = [
                'controllerCalls' => $result['controllerCalls'],
                'ajax' => $result['ajax'],
                'errors' => $result['errors'],
            ];

            if ($reference === null) {
                $reference = $signature;
            } else {
                $this->assertSame($reference, $signature, "{$label}: missing-partnerType guard parity mismatch");
            }
        }

        $this->assertSame([], $reference['ajax']);
        $this->assertSame([], $reference['errors']);
    }

    public function testOutputParity_RenderLoopSingleTransaction_AcrossSnapshots(): void
    {
        $sources = [
            'april' => $this->loadSourceFromCommit(self::APRIL_2025_COMMIT),
            'november' => $this->loadSourceFromCommit(self::NOV_2025_COMMIT),
            'baselined' => $this->loadSourceFromCommit(self::BASELINED_COMMIT),
            'restored' => $this->loadCurrentSource(),
        ];

        $trzs = [
            'tx-1' => [
                ['id' => 1, 'transactionAmount' => 10],
            ],
        ];

        $reference = null;
        $tested = 0;

        foreach ($sources as $label => $source) {
            if (strpos($source, 'foreach($trzs as $trz_code => $trz_data)') === false) {
                continue;
            }

            $tested++;
            $block = $this->extractRenderLoopBlock($source);
            $result = $this->runRenderLoopBlock($block, $trzs);

            if ($reference === null) {
                $reference = $result;
            } else {
                $this->assertSame($reference, $result, "{$label}: single-transaction render loop parity mismatch");
            }
        }

        $this->assertGreaterThanOrEqual(2, $tested, 'Expected at least 2 comparable snapshots for single-transaction render parity.');
        $this->assertSame([1], $reference['displayed']);
    }

    public function testOutputParity_RenderLoopThreeTransactions_MidStream_AcrossSnapshots(): void
    {
        $sources = [
            'april' => $this->loadSourceFromCommit(self::APRIL_2025_COMMIT),
            'november' => $this->loadSourceFromCommit(self::NOV_2025_COMMIT),
            'baselined' => $this->loadSourceFromCommit(self::BASELINED_COMMIT),
            'restored' => $this->loadCurrentSource(),
        ];

        $trzs = [
            'tx-1' => [['id' => 1, 'transactionAmount' => 10]],
            'tx-2' => [['id' => 2, 'transactionAmount' => 20]],
            'tx-3' => [['id' => 3, 'transactionAmount' => 30]],
        ];

        $reference = null;
        $tested = 0;

        foreach ($sources as $label => $source) {
            if (strpos($source, 'foreach($trzs as $trz_code => $trz_data)') === false) {
                continue;
            }

            $tested++;
            $block = $this->extractRenderLoopBlock($source);
            $result = $this->runRenderLoopBlock($block, $trzs);

            if ($reference === null) {
                $reference = $result;
            } else {
                $this->assertSame($reference, $result, "{$label}: three-transaction render loop parity mismatch");
            }
        }

        $this->assertGreaterThanOrEqual(2, $tested, 'Expected at least 2 comparable snapshots for three-transaction render parity.');
        $this->assertSame([1, 2, 3], $reference['displayed']);
    }
}

class ProcessStatementsOutputParity_ControllerSpy
{
    public $calls = [];
    public $charge = 0.0;

    public function unsetTrans(): void { $this->calls[] = 'unsetTrans'; }
    public function addCustomer(): void { $this->calls[] = 'addCustomer'; }
    public function addVendor(): void { $this->calls[] = 'addVendor'; }
    public function toggleDebitCredit(): void { $this->calls[] = 'toggleDebitCredit'; }

    public function processSupplierTransaction(): void { $this->calls[] = 'processSupplierTransaction'; }
    public function processCustomerPayment(): void { $this->calls[] = 'processCustomerPayment'; }
    public function processTransactions(): void { $this->calls[] = 'processTransactions'; }

    public function sumCharges($tid) { return 0.0; }
    public function set($name, $value): void { $this->calls[] = 'set:' . $name; }
}

class ProcessStatementsOutputParity_AjaxSpy
{
    public function activate(string $id): void
    {
        ProcessStatementsOutputParityTest::$fx['ajax'][] = $id;
    }
}

class ProcessStatementsOutputParity_LineItemStub
{
    private $id;

    public function __construct($trz, $vendorList, $optypes)
    {
        $this->id = isset($trz['id']) ? (int)$trz['id'] : 0;
        ProcessStatementsOutputParityTest::$fx['renderConstructed'][] = $this->id;
    }

    public function display()
    {
        ProcessStatementsOutputParityTest::$fx['renderDisplayed'][] = $this->id;
    }
}

if (!function_exists('display_error')) {
    function display_error($message): void
    {
        ProcessStatementsOutputParityTest::$fx['errors'][] = (string)$message;
    }
}

if (!function_exists('ProcessStatementsOutputParity_capture_error')) {
    function ProcessStatementsOutputParity_capture_error($message): void
    {
        ProcessStatementsOutputParityTest::$fx['errors'][] = (string)$message;
    }
}

if (!function_exists('display_notification')) {
    function display_notification($message): void
    {
        ProcessStatementsOutputParityTest::$fx['notifications'][] = (string)$message;
    }
}

if (!function_exists('ProcessStatementsOutputParity_capture_notification')) {
    function ProcessStatementsOutputParity_capture_notification($message): void
    {
        ProcessStatementsOutputParityTest::$fx['notifications'][] = (string)$message;
    }
}

if (!function_exists('fa_get_bank_account_by_number')) {
    function fa_get_bank_account_by_number($accountNumber)
    {
        return ProcessStatementsOutputParityTest::$fx['bankAccountByNumber'][$accountNumber] ?? [];
    }
}

if (!function_exists('get_bank_account_by_number')) {
    function get_bank_account_by_number($accountNumber)
    {
        return ProcessStatementsOutputParityTest::$fx['bankAccountByNumber'][$accountNumber] ?? [];
    }
}

if (!function_exists('get_transaction')) {
    function get_transaction($id)
    {
        $tx = ProcessStatementsOutputParityTest::$fx['transaction'];
        $tx['id'] = $id;
        return $tx;
    }
}

if (!class_exists('bi_transactions_model')) {
    class bi_transactions_model
    {
        public function get_transaction($tid)
        {
            return ProcessStatementsOutputParityTest::$fx['transaction'];
        }

        public function get_transactions($status = null)
        {
            ProcessStatementsOutputParityTest::$fx['getTransactionsArgs'][] = $status;
            return [];
        }
    }
}

if (!class_exists('ProcessStatementsOutputParity_BiTransactionsModelStub')) {
    class ProcessStatementsOutputParity_BiTransactionsModelStub
    {
        public function get_transaction($tid)
        {
            return ProcessStatementsOutputParityTest::$fx['transaction'];
        }

        public function get_transactions($status = null)
        {
            ProcessStatementsOutputParityTest::$fx['getTransactionsArgs'][] = $status;
            return [];
        }
    }
}

// Polyfill for environments where each() is removed.
if (!function_exists('each')) {
    function each(&$array)
    {
        if (!is_array($array)) {
            return false;
        }
        $key = key($array);
        if ($key === null) {
            return false;
        }
        $value = current($array);
        next($array);

        return [
            0 => $key,
            1 => $value,
            'key' => $key,
            'value' => $value,
        ];
    }
}
