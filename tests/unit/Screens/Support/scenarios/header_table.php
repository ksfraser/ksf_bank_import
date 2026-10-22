<?php

declare(strict_types=1);

/**
 * @package    Ksfraser\FaBankImport\Tests\Unit\Screens\Support\scenarios
 * @license    MIT
 */

$config = $GLOBALS['screen_config'];

$_POST = isset($config['post']) && is_array($config['post']) ? $config['post'] : [];

if (!isset($config['script']) || !is_file($config['script'])) {
    fwrite(STDERR, "The scenario configuration does not name the header table.\n");
    exit(2);
}

require $config['script'];

/**
 * A header table with all of its filter cells filled in.
 */
class screen_filter_table extends ksf_modules_table_filter_by_date
{
    /**
     * Fills the six filter cells the base class can render.
     *
     * @return void
     */
    public function configure(): void
    {
        $this->cell1 = ['label' => 'Bank Account', 'var_name' => 'bank_account', 'type' => 'text_input', 'options' => []];
        $this->cell2 = ['label' => 'Status', 'var_name' => 'status', 'type' => 'text_input', 'options' => []];
        $this->cell3 = ['label' => 'From', 'var_name' => 'from_date', 'type' => 'text_input', 'options' => []];
        $this->cell4 = ['label' => 'To', 'var_name' => 'to_date', 'type' => 'text_input', 'options' => []];
        $this->cell5 = ['label' => 'Account', 'var_name' => 'account', 'type' => 'text_input', 'options' => []];
        $this->cell6 = ['label' => 'Reference', 'var_name' => 'reference', 'type' => 'text_input', 'options' => []];
    }
}

$table = new screen_filter_table();
$table->configure();

$methods = isset($config['methods']) && is_array($config['methods'])
    ? $config['methods']
    : ['display', 'bank_import_header'];

if (in_array('display', $methods, true)) {
    $table->display();
}

if (in_array('bank_import_header', $methods, true)) {
    $table->bank_import_header();
}
