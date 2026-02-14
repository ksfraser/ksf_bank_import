<?php

use PHPUnit\Framework\TestCase;

/**
 * Logic characterization tests for process_statements variants.
 *
 * These tests focus on control-flow parity (if/switch/foreach) rather than
 * brittle full-file string snapshots.
 */
class ProcessStatementsLogicCharacterizationTest extends TestCase
{
    private function loadFileFromCommit(string $commitHash, string $repoRelativePath): string
    {
        $repoRoot = realpath(__DIR__ . '/../../');
        $this->assertNotFalse($repoRoot, 'Could not resolve repository root path');

        $command = sprintf(
            'git -C %s show %s:%s 2>NUL',
            escapeshellarg($repoRoot),
            escapeshellarg($commitHash),
            escapeshellarg(str_replace('\\', '/', $repoRelativePath))
        );

        $content = shell_exec($command);
        if (!is_string($content) || $content === '') {
            $this->markTestSkipped("Unable to load {$repoRelativePath} from commit {$commitHash}");
        }

        return $content;
    }

    private function loadFile(string $relativePath): string
    {
        $fullPath = __DIR__ . '/../../' . $relativePath;
        $this->assertFileExists($fullPath, "Missing file: {$relativePath}");

        $content = file_get_contents($fullPath);
        $this->assertNotFalse($content, "Could not read file: {$relativePath}");

        return $content;
    }

    private function extractPartnerTypeCases(string $content): array
    {
        preg_match_all("/case\\s*\\(\\s*\\\$_POST\\['partnerType'\\]\\[\\\$k\\]\\s*==\\s*'([A-Z]{2})'[^\\)]*\\)\\s*:/", $content, $matches);
        $cases = $matches[1] ?? [];
        sort($cases);
        return $cases;
    }

    public function testCurrentAndPrecleanShareCoreControlFlow(): void
    {
        $current = $this->loadFile('process_statements.php');
        $preclean = $this->loadFile('src/Ksfraser/FaBankImport/process_statements_preclean.php');

        foreach ([$current, $preclean] as $content) {
            $this->assertMatchesRegularExpression('/if\s*\(\s*isset\s*\(\s*\$_POST\[\'ProcessTransaction\'\]\s*\)\s*\)/', $content);
            $this->assertMatchesRegularExpression('/switch\s*\(\s*true\s*\)/', $content);
            $this->assertMatchesRegularExpression('/if\s*\(\s*\$_POST\[\'statusFilter\'\]\s*==\s*0\s*OR\s*\$_POST\[\'statusFilter\'\]\s*==\s*1\s*\)/', $content);
            $this->assertMatchesRegularExpression('/foreach\s*\(\s*\$trzs\s+as\s+\$trz_code\s*=>\s*\$trz_data\s*\)/', $content);
            $this->assertMatchesRegularExpression('/foreach\s*\(\s*\$trz_data\s+as\s+\$idx\s*=>\s*\$trz\s*\)/', $content);
        }

        $expectedCases = ['BT', 'CU', 'MA', 'QE', 'SP', 'ZZ'];
        $this->assertSame($expectedCases, $this->extractPartnerTypeCases($current));
        $this->assertSame($expectedCases, $this->extractPartnerTypeCases($preclean));
    }

    public function testCurrentRoutingCallsExpectedControllerMethods(): void
    {
        $current = $this->loadFile('process_statements.php');

        $this->assertStringContainsString("case (\$_POST['partnerType'][\$k] == 'SP'):", $current);
        $this->assertStringContainsString('$bi_controller->processSupplierTransaction();', $current);

        $this->assertStringContainsString("case (\$_POST['partnerType'][\$k] == 'CU'):", $current);
        $this->assertStringContainsString('$bi_controller->processCustomerPayment();', $current);

        foreach (['QE', 'BT', 'MA', 'ZZ'] as $type) {
            $this->assertStringContainsString("case (\$_POST['partnerType'][\$k] == '{$type}'):", $current);
        }

        $genericCalls = substr_count($current, '$bi_controller->processTransactions();');
        $this->assertGreaterThanOrEqual(4, $genericCalls, 'Expected generic processing for QE/BT/MA/ZZ');
    }

    public function testCurrentEnhancementsArePresentWithSafeFallbacks(): void
    {
        $current = $this->loadFile('process_statements.php');

        $this->assertStringContainsString("if (class_exists('\\\\Ksfraser\\\\PartnerTypes\\\\PartnerTypeRegistry'))", $current);
        $this->assertStringContainsString("if (isset(\$_POST['ProcessBothSides']))", $current);
        $this->assertStringContainsString('PairedTransferDualSideAction', $current);
        $this->assertStringContainsString('VendorListManager::getInstance()->getVendorList()', $current);
        $this->assertStringContainsString('$vendor_list = array();', $current);
    }

    public function testRefactoredSnapshotHasCoreProcessTransactionIntent(): void
    {
        $refactoredPath = __DIR__ . '/../../src/Ksfraser/FaBankImport/process_statements.copilot_refactored.php';
        if (!is_file($refactoredPath)) {
            $this->markTestSkipped('Refactored snapshot file not available.');
        }

        $content = file_get_contents($refactoredPath);
        $this->assertNotFalse($content);

        $this->assertMatchesRegularExpression('/if\s*\(\s*isset\s*\(\s*\$_POST\[\'ProcessTransaction\'\]\s*\)\s*\)/', $content);
        $this->assertMatchesRegularExpression('/switch\s*\(\s*true\s*\)/', $content);
        $this->assertStringContainsString("case (\$_POST['partnerType'][\$k] == 'SP'):", $content);
        $this->assertStringContainsString("case (\$_POST['partnerType'][\$k] == 'CU'", $content);
    }

    public function testApril9AndNov14SnapshotsHaveCoreControlFlow(): void
    {
        // April 9, 2025 checkpoint mentioned by team
        $aprilContent = $this->loadFileFromCommit('bda933c', 'process_statements.php');

        // Nov 14, 2025 merge checkpoint that reintroduced prod code
        $novemberContent = $this->loadFileFromCommit('524664f', 'process_statements.php');

        foreach ([$aprilContent, $novemberContent] as $content) {
            $this->assertMatchesRegularExpression('/if\s*\(\s*isset\s*\(\s*\$_POST\[\'ProcessTransaction\'\]\s*\)\s*\)/', $content);

            if (preg_match('/switch\s*\(\s*true\s*\)/', $content) === 1) {
                $cases = $this->extractPartnerTypeCases($content);
                $this->assertContains('SP', $cases);
                $this->assertContains('BT', $cases);
                $this->assertContains('MA', $cases);
                $this->assertContains('ZZ', $cases);
            } else {
                // Some historical snapshots used controller-style refactor.
                $this->assertStringContainsString('class ProcessStatementsController', $content);
                $this->assertMatchesRegularExpression('/switch\s*\(\s*\$partnerType\s*\)/', $content);
                $this->assertStringContainsString('processSupplierTransaction', $content);
                $this->assertStringContainsString('processCustomerTransaction', $content);
                $this->assertStringContainsString('processBankTransfer', $content);
            }
        }
    }
}
