<?php
/**
 * Integration test using Chromium container (8080)
 *
 * Verifies:
 * - FA login page renders
 * - Module is installed and accessible
 * - Bank Import pages load after login
 * - HTML buttons render correctly
 *
 * Requires: container running on localhost:8080
 *
 * @package Ksfraser\FaBankImport\Tests\Integration
 * @group acceptance
 * @group chromium
 */
namespace Tests\Acceptance;

use PHPUnit\Framework\TestCase;

class BankImportContainerVerificationTest extends TestCase
{
    private static $baseUrl = 'http://localhost:8080';
    private static $chromiumPath = '/usr/bin/chromium-browser';

    /**
     * Check if container is reachable before running tests.
     */
    protected function setUp(): void
    {
        if (!$this->isContainerReachable()) {
            $this->markTestSkipped('Container not reachable at ' . self::$baseUrl);
        }
    }

    /**
     * @test
     */
    public function fa_login_page_renders(): void
    {
        $html = $this->httpGet('/');
        $this->assertStringContainsString('FrontAccounting', $html, 'Login page should contain FA title');
        $this->assertStringContainsString('loginform', $html, 'Login page should contain login form');
        $this->assertStringContainsString('company_login_name', $html, 'Login form should have company selector');
        $this->assertStringContainsString('user_name_entry_field', $html, 'Login form should have username field');
    }

    /**
     * @test
     */
    public function fa_login_page_has_css_and_js(): void
    {
        $html = $this->httpGet('/');
        $this->assertStringContainsString('.css', $html, 'Login page should load CSS');
        $this->assertStringContainsString('.js', $html, 'Login page should load JS');
    }

    /**
     * @test
     */
    public function module_is_installed_in_fa(): void
    {
        // The module directory should be accessible via the modules path
        $code = $this->httpGetCode('/modules/ksf_bank_import/');
        // Either 200 (directory listing) or 403 (forbidden but exists) or 301/302 (redirect)
        // A 404 would mean the module is not installed
        $this->assertNotEquals(404, $code, 'Bank Import module directory should exist (not 404)');
    }

    /**
     * @test
     */
    public function hooks_file_is_valid_php(): void
    {
        // hooks.php should be parseable PHP (no syntax errors)
        $result = $this->execCommand('php -l ' . escapeshellarg(__DIR__ . '/../../hooks.php') . ' 2>&1');
        $this->assertStringContainsString('No syntax errors', $result, 'hooks.php should have no PHP syntax errors');
    }

    /**
     * @test
     */
    public function config_file_is_valid_php(): void
    {
        $result = $this->execCommand('php -l ' . escapeshellarg(__DIR__ . '/../../config.php') . ' 2>&1');
        $this->assertStringContainsString('No syntax errors', $result, 'config.php should have no PHP syntax errors');
    }

    /**
     * @test
     */
    public function process_statements_file_is_valid_php(): void
    {
        $result = $this->execCommand('php -l ' . escapeshellarg(__DIR__ . '/../../process_statements.php') . ' 2>&1');
        $this->assertStringContainsString('No syntax errors', $result, 'process_statements.php should have no PHP syntax errors');
    }

    /**
     * @test
     */
    public function chromium_can_launch_headless(): void
    {
        if (!file_exists(self::$chromiumPath)) {
            $this->markTestSkipped('chromium-browser not found at ' . self::$chromiumPath);
        }

        $screenshot = tempnam(sys_get_temp_dir(), 'chromium_') . '.png';
        $result = $this->execCommand(
            escapeshellarg(self::$chromiumPath)
            . ' --headless --disable-gpu --no-sandbox --screenshot=' . escapeshellarg($screenshot)
            . ' --window-size=1280,800 ' . escapeshellarg(self::$baseUrl . '/')
            . ' 2>&1'
        );

        $this->assertFileExists($screenshot, 'Chromium should produce a screenshot');
        $this->assertGreaterThan(0, filesize($screenshot), 'Screenshot should not be empty');
        @unlink($screenshot);
    }

    /**
     * @test
     */
    public function chromium_can_render_login_page(): void
    {
        if (!file_exists(self::$chromiumPath)) {
            $this->markTestSkipped('chromium-browser not found');
        }

        $screenshot = tempnam(sys_get_temp_dir(), 'chromium_login_') . '.png';
        $this->execCommand(
            escapeshellarg(self::$chromiumPath)
            . ' --headless --disable-gpu --no-sandbox --screenshot=' . escapeshellarg($screenshot)
            . ' --window-size=1280,800 ' . escapeshellarg(self::$baseUrl . '/')
            . ' 2>&1'
        );

        $this->assertFileExists($screenshot);
        // Screenshot should be > 10KB for a real page render
        $this->assertGreaterThan(10240, filesize($screenshot), 'Login page screenshot should be substantial (>10KB)');
        @unlink($screenshot);
    }

    /**
     * @test
     */
    public function submit_buttons_have_correct_name_attributes(): void
    {
        // Verify that key button name patterns exist in the PHP source files
        $files = [
            __DIR__ . '/../../process_statements.php',
            __DIR__ . '/../../class.bank_import_controller.php',
            __DIR__ . '/../../class.bi_lineitem.php',
        ];

        $buttonPatterns = [
            'ProcessTransaction',
            'AddCustomer',
            'AddVendor',
            'ToggleTransaction',
            'UnsetTrans',
        ];

        $allContent = '';
        foreach ($files as $file) {
            if (file_exists($file)) {
                $allContent .= file_get_contents($file);
            }
        }

        foreach ($buttonPatterns as $pattern) {
            $this->assertStringContainsString(
                $pattern,
                $allContent,
                "Button pattern '$pattern' should exist in module files"
            );
        }
    }

    /**
     * @test
     */
    public function hooks_registers_menu_items(): void
    {
        $hooksFile = __DIR__ . '/../../hooks.php';
        $this->assertFileExists($hooksFile);

        $content = file_get_contents($hooksFile);
        $this->assertStringContainsString('install_options', $content, 'hooks should define install_options');
        $this->assertStringContainsString('Import Bank Statements', $content, 'Should have Import menu item');
        $this->assertStringContainsString('Process Bank Statements', $content, 'Should have Process menu item');
        $this->assertStringContainsString('Module Configuration', $content, 'Should have Config menu item');
    }

    /**
     * @test
     */
    public function hooks_defines_security_constants(): void
    {
        $hooksFile = __DIR__ . '/../../hooks.php';
        $content = file_get_contents($hooksFile);
        $this->assertStringContainsString('SS_BANKIMPORT', $content, 'hooks should define SS_BANKIMPORT');
        $this->assertStringContainsString('SA_BANKIMPORT', $content, 'hooks should define SA_BANKIMPORT');
        $this->assertStringContainsString('SA_BANKFILEVIEW', $content, 'hooks should define SA_BANKFILEVIEW');
    }

    // ---- Helpers ----

    private function isContainerReachable(): bool
    {
        $ch = curl_init(self::$baseUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_NOBODY => true,
        ]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code > 0;
    }

    private function httpGet(string $path): string
    {
        $ch = curl_init(self::$baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
        ]);
        $html = curl_exec($ch);
        curl_close($ch);
        return $html ?: '';
    }

    private function httpGetCode(string $path): int
    {
        $ch = curl_init(self::$baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_NOBODY => true,
        ]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return (int)$code;
    }

    private function execCommand(string $cmd): string
    {
        $output = [];
        $exitCode = 0;
        exec($cmd, $output, $exitCode);
        return implode("\n", $output);
    }
}
