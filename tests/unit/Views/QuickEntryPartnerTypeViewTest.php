<?php

/**
 * DEPRECATED - QuickEntryPartnerTypeViewTest
 * 
 * This test has been deprecated due to missing view file.
 * 
 * Reason for deprecation:
 * - References Views/QuickEntryPartnerTypeView.v2.php which does not exist
 * - Tests incomplete/work-in-progress v2 view refactoring
 * - Blocking test suite without providing production coverage
 * 
 * Status:
 * ✗ View file does not exist: Views/QuickEntryPartnerTypeView.v2.php
 * ✗ Not part of approved test suite
 * 
 * Restoration:
 * If QuickEntryPartnerTypeView v2 implementation is needed:
 * 1. Create Views/QuickEntryPartnerTypeView.v2.php
 * 2. Uncomment original tests from git history
 * 3. Verify HtmlLabelRow namespace is available from vendor/ksfraser/html
 */

namespace KsfBankImport\Tests\Unit\Views;

use PHPUnit\Framework\TestCase;

/**
 * @deprecated This class is no longer maintained
 */
class QuickEntryPartnerTypeViewTest extends TestCase
{
    /**
     * Placeholder test - all actual tests moved to git history
     * 
     * @test
     */
    public function testDeprecated()
    {
        $this->markTestSkipped('QuickEntryPartnerTypeViewTest deprecated - view file Views/QuickEntryPartnerTypeView.v2.php does not exist');
    }
}
