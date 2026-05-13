<?php

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Global namespace block: define generate_csrf_token() as a global function so
// that the production code's `function_exists('generate_csrf_token')` check
// resolves to TRUE when this file is loaded.
//
// PHP's `function_exists('generate_csrf_token')` always checks the global
// namespace regardless of the calling namespace; therefore the function MUST
// be defined here (not in a sub-namespace) to be detected by ReconcileView.
// ---------------------------------------------------------------------------

namespace {
    if (!function_exists('generate_csrf_token')) {
        function generate_csrf_token(): string
        {
            return $GLOBALS['_test_csrf_token'] ?? '';
        }
    }
}

// ---------------------------------------------------------------------------
// Test class
// ---------------------------------------------------------------------------

namespace Ksfraser\FaBankImport\Tests\StatementReconcile\Application {

use Ksfraser\FaBankImport\StatementReconcile\Application\ReconcileView;
use PHPUnit\Framework\TestCase;

/**
 * Tests that csrfField() uses generate_csrf_token() when the function is
 * defined in the global namespace.
 *
 * A separate file is needed because the global namespace block above defines
 * generate_csrf_token() for the entire PHP process; ReconcileViewCsrfFallbackTest
 * (which must test the session-fallback path) requires that function to be
 * absent, and therefore runs @runInSeparateProcess while living in a file that
 * does NOT have the global block.
 *
 * @covers \Ksfraser\FaBankImport\StatementReconcile\Application\ReconcileView
 */
class ReconcileViewCsrfTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['_test_csrf_token'] = '';
    }

    protected function tearDown(): void
    {
        $GLOBALS['_test_csrf_token'] = '';
    }

    // ------------------------------------------------------------------
    // Lines 782-783: generate_csrf_token() IS defined globally.
    // ------------------------------------------------------------------

    public function testCsrfFieldUsesGenerateCsrfTokenWhenAvailable(): void
    {
        $GLOBALS['_test_csrf_token'] = 'csrf_test_token_abc';

        ob_start();
        (new ReconcileView())->renderUploadForm();
        $output = ob_get_clean();

        $this->assertStringContainsString('csrf_test_token_abc', $output);
    }
}

} // end namespace
