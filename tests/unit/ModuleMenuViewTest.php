<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../views/module_menu_view.php';

class ModuleMenuViewTest extends TestCase
{
    public function testRenderMenuContainsAllExpectedLinksAndNoExtras()
    {
        $expectedLinks = [
            'process_statements.php',
            'import_statements.php',
            'manage_partners_data.php',
            'view_statements.php',
            'manage_uploaded_files.php',
            'view_import_logs.php',
            'validate_gl_entries.php',
            'schema_maintenance.php',
            'transfer_match_review.php',
            'module_config.php',
            'audit_files.php',
        ];

        $menu = new \Views\ModuleMenuView();
        ob_start();
        $menu->renderMenu();
        $output = ob_get_clean();

        // Check all expected links are present
        foreach ($expectedLinks as $link) {
            $this->assertStringContainsString("><a href=\"$link\"", $output, "Menu missing link: $link");
        }

        // Check there are no extra links
        preg_match_all('/<a href=\"([^\"]+)\"/', $output, $matches);
        $actualLinks = $matches[1];
        sort($expectedLinks);
        sort($actualLinks);
        $this->assertSame($expectedLinks, $actualLinks, 'Menu contains unexpected links.');
    }
}
