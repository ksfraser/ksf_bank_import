<?php

declare(strict_types=1);

/**
 * @package    Ksfraser\FaBankImport\Tests\Unit\Screens\Support
 * @license    MIT
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Screens\Support;

/**
 * Builds the throw-away directory tree the procedural screens expect.
 *
 * The bank-import screens are FrontAccounting page scripts: they resolve
 * their includes relative to the working directory and expect to live in
 * <fa-root>/modules/bank_import. This scaffold recreates that layout in a
 * temporary directory so the real screens can be executed without a live
 * FrontAccounting installation.
 *
 * Two layouts are supported:
 *
 *  - 'fa'     the working directory is <tmp>/fa/modules/bank_import, the
 *             FrontAccounting root is <tmp>/fa;
 *  - 'origin' the working directory is <tmp>/ksf_bank_import and the sibling
 *             directory <tmp>/ksf_modules_common holds the legacy `origin`
 *             base class expected by header_table.php.
 */
final class ScreenScaffold
{
    /**
     * @var string Temporary root of the scaffold.
     */
    private $root;

    /**
     * @var string Working directory the screen is executed in.
     */
    private $cwd;

    /**
     * Creates the scaffold.
     *
     * @param string $mode Either 'fa' or 'origin'.
     */
    public function __construct(string $mode = 'fa')
    {
        $this->root = sys_get_temp_dir() . '/ksf_screen_' . uniqid('', true);
        if (!mkdir($this->root, 0777, true) && !is_dir($this->root)) {
            throw new \RuntimeException('Unable to create the screen scaffold ' . $this->root);
        }

        if ($mode === 'origin') {
            $this->buildOriginLayout();
            return;
        }

        $this->buildFaLayout();
    }

    /**
     * The working directory the screen has to be executed in.
     *
     * @return string
     */
    public function cwd(): string
    {
        return $this->cwd;
    }

    /**
     * Removes the scaffold again.
     *
     * @return void
     */
    public function cleanup(): void
    {
        self::remove($this->root);
    }

    /**
     * The repository root.
     *
     * @return string
     */
    public static function repoRoot(): string
    {
        return dirname(__DIR__, 4);
    }

    /**
     * Recursively removes a directory.
     *
     * @param string $path Directory to remove.
     *
     * @return void
     */
    private static function remove(string $path): void
    {
        if ($path === '' || !is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if (is_array($items)) {
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                $child = $path . '/' . $item;
                if (is_link($child)) {
                    @unlink($child);
                    continue;
                }
                if (is_dir($child)) {
                    self::remove($child);
                    continue;
                }
                @unlink($child);
            }
        }

        @rmdir($path);
    }

    /**
     * Creates a directory, including its parents.
     *
     * @param string $path Directory path.
     *
     * @return void
     */
    private static function makeDir(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
            throw new \RuntimeException('Unable to create directory ' . $path);
        }
    }

    /**
     * Writes a file, creating the parent directory when needed.
     *
     * @param string $path    File path.
     * @param string $content File content.
     *
     * @return void
     */
    private static function write(string $path, string $content): void
    {
        self::makeDir(dirname($path));
        file_put_contents($path, $content);
    }

    /**
     * Symlinks a repository file into the scaffold.
     *
     * @param string $target Absolute path of the real file.
     * @param string $link   Absolute path of the link.
     *
     * @return void
     */
    private static function link(string $target, string $link): void
    {
        self::makeDir(dirname($link));
        if (!is_link($link) && !file_exists($link)) {
            symlink($target, $link);
        }
    }

    /**
     * Builds the FrontAccounting/module layout.
     *
     * @return void
     */
    private function buildFaLayout(): void
    {
        $repo = self::repoRoot();
        $faRoot = $this->root . '/fa';
        $moduleDir = $faRoot . '/modules/bank_import';

        self::makeDir($moduleDir);
        $this->cwd = $moduleDir;

        // FrontAccounting core stubs.
        self::write($faRoot . '/includes/session.inc', $this->sessionStub());
        self::write($faRoot . '/includes/date_functions.inc', "<?php\n// FrontAccounting date helpers are not needed by the screens.\n");
        self::write($faRoot . '/includes/ui.inc', "<?php\n// FrontAccounting UI helpers are provided by the mock package.\n");
        self::write($faRoot . '/includes/data_checks.inc', "<?php\n// FrontAccounting data checks are not needed by the screens.\n");
        self::write($faRoot . '/includes/ui/ui_input.inc', "<?php\n");
        self::write($faRoot . '/includes/ui/ui_lists.inc', "<?php\n");
        self::write($faRoot . '/includes/ui/ui_globals.inc', "<?php\n");
        self::write($faRoot . '/gl/includes/gl_ui.inc', "<?php\n");

        // Module includes: the real implementations are reused so the screens
        // behave exactly like they do in production.
        $includes = $moduleDir . '/includes/';
        foreach (['parser.php', 'ro_wmmc_csv_parser.php', 'banking.php', 'parsers.inc'] as $file) {
            self::link($repo . '/includes/' . $file, $includes . $file);
        }

        // The QFX parser is copied (not linked) so that its __DIR__ points at
        // the scaffold and it picks up the scaffold's includes.inc.
        self::write($includes . 'qfx_parser.php', (string) file_get_contents($repo . '/includes/qfx_parser.php'));

        // includes.inc pulls in the transaction model; the scaffold loads the
        // model itself (see below) so that the model can be made to fail.
        $includesInc = (string) file_get_contents($repo . '/includes/includes.inc');
        $includesInc = str_replace(
            "require_once( __DIR__ . '/../class.bi_transactions.php' );",
            '// The transaction model is loaded by the screen harness instead.',
            $includesInc
        );
        self::write($includes . 'includes.inc', $includesInc);

        // pdata.inc declares search_partner_by_bank_account(), which the shared
        // mock package already provides, so the real file cannot be loaded
        // next to it. The stand-in keeps the two functions the screen uses.
        self::write($includes . 'pdata.inc', $this->pdataStub());

        // The module menu view and the legacy models.
        self::link($repo . '/src/Ksfraser/FaBankImport/views/module_menu_view.php', $moduleDir . '/Views/module_menu_view.php');
        self::link($repo . '/class.bi_statements.php', $moduleDir . '/class.bi_statements.php');
        self::write($moduleDir . '/class.bi_transactions.php', $this->transactionsModelStub($repo));

        // PEAR style HTML table used by manage_partners_data.php.
        self::write($moduleDir . '/HTML/Table.php', $this->htmlTableStub());
    }

    /**
     * Builds the layout expected by header_table.php.
     *
     * @return void
     */
    private function buildOriginLayout(): void
    {
        $this->cwd = $this->root . '/ksf_bank_import';
        self::makeDir($this->cwd);
        self::write($this->root . '/ksf_modules_common/class.origin.php', $this->originStub());
    }

    /**
     * The FrontAccounting session stub.
     *
     * @return string
     */
    private function sessionStub(): string
    {
        return <<<'PHP'
<?php
// Minimal FrontAccounting session stub for the bank-import screens.
global $Ajax, $use_popup_windows, $use_date_picker;
$Ajax = new \Ksfraser\FaBankImport\Tests\Unit\Screens\Support\ScreenAjax();
$use_popup_windows = true;
$use_date_picker = true;
PHP;
    }

    /**
     * The legacy `origin` base class stub.
     *
     * @return string
     */
    private function originStub(): string
    {
        return <<<'PHP'
<?php
// Minimal stand-in for ksf_modules_common's origin base class.
class origin
{
    public function __construct()
    {
    }
}
PHP;
    }

    /**
     * The transaction model stand-in.
     *
     * The real model is used, but the harness can make its constructor fail
     * so that the screen's error handling can be exercised.
     *
     * @param string $repo Repository root.
     *
     * @return string
     */
    private function transactionsModelStub(string $repo): string
    {
        $real = $repo . '/class.bi_transactions.php';

        return <<<PHP
<?php
// Loads the module's transaction model.
//
// The harness can make the constructor fail so that the screen's error
// handling around "new bi_transactions_model()" can be exercised.
if (!class_exists('bi_transactions_model', false)) {
    if (!empty(\$GLOBALS['__screen_bi_transactions_throws'])) {
        class bi_transactions_model extends generic_fa_interface_model
        {
            public function __construct()
            {
                throw new \\Exception('the transaction model is not available');
            }
        }
    } else {
        require_once '{$real}';
    }
}
PHP;
    }

    /**
     * The partner data stand-in.
     *
     * @return string
     */
    private function pdataStub(): string
    {
        return <<<'PHP'
<?php
// Stand-in for the module's includes/pdata.inc.
//
// The real file declares search_partner_by_bank_account(), which the shared
// FrontAccounting mock package already provides; loading both is fatal. The
// two functions the Manage Partners screen uses keep their real behaviour.
function get_partner_data($partner_id, $partner_type, $partner_detail_id)
{
    $sql = "SELECT * FROM " . TB_PREF . "bi_partners_data"
        . " WHERE partner_id=" . db_escape($partner_id)
        . " AND partner_type=" . db_escape($partner_type);
    if ($partner_type == PT_CUSTOMER || $partner_type == ST_BANKTRANSFER) {
        $sql .= " AND partner_detail_id=" . db_escape($partner_detail_id);
    }
    $result = db_query($sql, "could not get partner data");

    return db_fetch($result);
}

function set_partner_data($partner_id, $partner_type, $partner_detail_id, $data)
{
    $arr = get_partner_data($partner_id, $partner_type, $partner_detail_id);
    if (is_array($arr) && count($arr) > 0 && $arr['data'] == $data) {
        return;
    }

    $sql = "INSERT INTO " . TB_PREF . "bi_partners_data(partner_id, partner_type, partner_detail_id, data) VALUES("
        . db_escape($partner_id) . "," . db_escape($partner_type) . "," . db_escape($partner_detail_id) . "," . db_escape($data) . ")"
        . " ON DUPLICATE KEY UPDATE data=" . db_escape($data);
    db_query($sql, 'Could not update partner');
}
PHP;
    }

    /**
     * A small stand-in for the PEAR HTML_Table class.
     *
     * @return string
     */    private function htmlTableStub(): string
    {
        return <<<'PHP'
<?php
// Minimal stand-in for the PEAR HTML_Table class.
class HTML_Table
{
    /** @var array */
    private $rows = [];

    /** @var array */
    private $attributes = [];

    /**
     * @param array $attributes Table attributes.
     */
    public function __construct($attributes = [])
    {
        $this->attributes = is_array($attributes) ? $attributes : [];
    }

    /**
     * Starts a new row.
     *
     * @param array $attributes Row attributes.
     *
     * @return void
     */
    public function addRow($attributes = [])
    {
        $this->rows[] = ['attributes' => is_array($attributes) ? $attributes : [], 'cells' => []];
    }

    /**
     * Adds a cell to the current row.
     *
     * @param string $content    Cell content.
     * @param array  $attributes Cell attributes.
     *
     * @return void
     */
    public function addCell($content, $attributes = [])
    {
        if (empty($this->rows)) {
            $this->addRow();
        }
        $index = count($this->rows) - 1;
        $this->rows[$index]['cells'][] = [
            'content' => $content,
            'attributes' => is_array($attributes) ? $attributes : [],
        ];
    }

    /**
     * Renders the table.
     *
     * @return string
     */
    public function toHtml()
    {
        $html = '<table';
        foreach ($this->attributes as $name => $value) {
            $html .= ' ' . $name . '="' . htmlspecialchars((string) $value) . '"';
        }
        $html .= '>';
        foreach ($this->rows as $row) {
            $html .= '<tr>';
            foreach ($row['cells'] as $cell) {
                $html .= '<td';
                foreach ($cell['attributes'] as $name => $value) {
                    $html .= ' ' . $name . '="' . htmlspecialchars((string) $value) . '"';
                }
                $html .= '>' . $cell['content'] . '</td>';
            }
            $html .= '</tr>';
        }

        return $html . '</table>';
    }
}
PHP;
    }
}
