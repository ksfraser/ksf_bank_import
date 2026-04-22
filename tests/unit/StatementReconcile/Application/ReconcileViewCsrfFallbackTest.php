<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\StatementReconcile\Application;

use Ksfraser\FaBankImport\StatementReconcile\Application\ReconcileView;
use PHPUnit\Framework\TestCase;

/**
 * Tests that csrfField() falls back to $_SESSION['token'] when the global
 * generate_csrf_token() function is NOT available.
 *
 * MUST run @runInSeparateProcess so that generate_csrf_token() defined in
 * ReconcileViewCsrfTest.php (loaded in the main phpunit process) does NOT
 * bleed into this test's process.  In the separate process only the autoloader
 * bootstrap and this file are loaded, so generate_csrf_token is undefined.
 *
 * @covers \Ksfraser\FaBankImport\StatementReconcile\Application\ReconcileView
 */
class ReconcileViewCsrfFallbackTest extends TestCase
{
    // ------------------------------------------------------------------
    // Lines 789-790: falls back to $_SESSION['token'] when no global function.
    // ------------------------------------------------------------------

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCsrfFieldUsesSessionTokenFallback(): void
    {
        // In this isolated process, generate_csrf_token() is NOT defined.
        // The ReconcileView::csrfField() private method should fall back to
        // $_SESSION['token'].
        $_SESSION['token'] = 'session_csrf_fallback_xyz';

        ob_start();
        (new ReconcileView())->renderUploadForm();
        $output = ob_get_clean();

        $this->assertStringContainsString('session_csrf_fallback_xyz', $output);
    }

    // ------------------------------------------------------------------
    // Line 793: csrfField() returns '' when no function and no session token.
    // ------------------------------------------------------------------

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCsrfFieldReturnsEmptyWhenNoTokenAvailable(): void
    {
        // generate_csrf_token() is NOT defined in this isolated process.
        // $_SESSION['token'] is also not set.
        unset($_SESSION['token']);

        ob_start();
        (new ReconcileView())->renderUploadForm();
        $output = (string) ob_get_clean();

        // The return '' branch means no hidden token field in the output.
        $this->assertStringNotContainsString('name="token"', $output);
    }
}
