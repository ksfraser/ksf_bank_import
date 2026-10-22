<?php

declare(strict_types=1);

/**
 * @package    Ksfraser\FaBankImport\Tests\Unit\Screens
 * @license    MIT
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Screens;

use Ksfraser\FaBankImport\Tests\Unit\Screens\Support\ScreenProcess;
use Ksfraser\FaBankImport\Tests\Unit\Screens\Support\ScreenScaffold;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/ScreenProcess.php';
require_once __DIR__ . '/Support/ScreenScaffold.php';

/**
 * Behaviour of the "Manage Partners Bank Accounts" screen
 * (manage_partners_data.php).
 */
final class ManagePartnersDataScreenTest extends TestCase
{
    /**
     * @var ScreenScaffold
     */
    private $scaffold;

    protected function setUp(): void
    {
        $this->scaffold = new ScreenScaffold('fa');
    }

    protected function tearDown(): void
    {
        $this->scaffold->cleanup();
    }

    /**
     * Runs the screen in a child process.
     *
     * @param array $config Scenario configuration.
     *
     * @return ScreenProcess
     */
    private function runScreen(array $config): ScreenProcess
    {
        $config['script'] = ScreenScaffold::repoRoot() . '/src/Ksfraser/FaBankImport/manage_partners_data.php';

        $process = (new ScreenProcess())->run('screen', $this->scaffold->cwd(), $config);
        $process->mergeCoverage($this);

        return $process;
    }

    public function testTheScreenRendersThePartnerTypeChooser(): void
    {
        $process = $this->runScreen([
            'post' => ['partner_type' => 'C', 'partner_id' => 1, 'partner_detail_id' => 0],
        ]);

        $output = $process->getStdout();

        $this->assertStringContainsString('Manage Partners Bank Accounts', $output);
        $this->assertStringContainsString('Choose:', $output);
        $this->assertStringContainsString('name="partner_type"', $output);
        $this->assertStringContainsString('Customer', $output);
        $this->assertStringContainsString('Supplier', $output);
    }

    public function testASupplierOffersTheSupplierListAndHidesTheDetailField(): void
    {
        $process = $this->runScreen([
            'post' => ['partner_type' => 'S', 'partner_id' => 2, 'partner_detail_id' => 0],
        ]);

        $output = $process->getStdout();

        $this->assertStringContainsString('Supplier:', $output);
        $this->assertStringContainsString("name='partner_id'", $output);
        $this->assertStringContainsString('name="partner_detail_id"', $output);
    }

    public function testACustomerWithBranchesOffersTheBranchList(): void
    {
        $process = $this->runScreen([
            'post' => ['partner_type' => 'C', 'partner_id' => 3, 'partner_detail_id' => 0],
            'customer_has_branches' => true,
        ]);

        $output = $process->getStdout();

        $this->assertStringContainsString('Customer:', $output);
        $this->assertStringContainsString('Branch:', $output);
        $this->assertStringContainsString("name='partner_detail_id'", $output);
    }

    public function testACustomerWithoutBranchesHidesTheDetailField(): void
    {
        $process = $this->runScreen([
            'post' => ['partner_type' => 'C', 'partner_id' => 3, 'partner_detail_id' => 0],
            'customer_has_branches' => false,
        ]);

        $output = $process->getStdout();

        $this->assertStringContainsString('Customer:', $output);
        $this->assertStringNotContainsString('Branch:', $output);
        $this->assertStringContainsString('name="partner_detail_id"', $output);
    }

    public function testAnUnknownPartnerTypeIsReported(): void
    {
        $process = $this->runScreen([
            'post' => ['partner_type' => 'X', 'partner_id' => 0, 'partner_detail_id' => 0],
        ]);

        $this->assertStringContainsString('something else', $process->getStdout());
    }

    public function testTheStoredIbanIsShownInTheTextArea(): void
    {
        $process = $this->runScreen([
            'post' => ['partner_type' => 'C', 'partner_id' => 3, 'partner_detail_id' => 0],
            'db_rows' => [
                [['data' => 'CA1234567890']],
            ],
        ]);

        $output = $process->getStdout();

        $this->assertStringContainsString('IBAN(S):', $output);
        $this->assertStringContainsString('CA1234567890', $output);
    }

    public function testARefreshedPartnerListSelectsTheDebtorOfTheChosenBranch(): void
    {
        $process = $this->runScreen([
            'post' => ['partner_type' => 'C', 'partner_id' => 0, 'partner_detail_id' => 9],
            'list_updated' => ['partner_detail_id' => true],
            'branches' => [9 => ['debtor_no' => 42]],
        ]);

        $info = $process->getInfo();

        $this->assertSame(['partner_id'], $info['ajax'] ?? []);
        $this->assertSame(42, $info['post']['partner_id'] ?? null);
    }

    public function testChangingThePartnerTypeClearsTheSelection(): void
    {
        $process = $this->runScreen([
            'post' => [
                'partner_type' => 'C',
                'partner_id' => 7,
                'partner_detail_id' => 3,
                'data' => 'CA1234567890',
                '_partner_type_update' => '1',
            ],
        ]);

        $info = $process->getInfo();

        $this->assertSame(['partner'], $info['ajax'] ?? []);
        $this->assertSame('data', $info['focus'] ?? null);
        $this->assertSame('', $info['post']['partner_id'] ?? null);
        $this->assertSame('', $info['post']['data'] ?? null);
    }

    public function testPressingUpdateStoresThePartnerData(): void
    {
        $process = $this->runScreen([
            'post' => [
                'partner_type' => 'C',
                'partner_id' => 5,
                'partner_detail_id' => 0,
                'data' => 'NEW-IBAN',
                'process' => 'Update',
            ],
            'db_rows' => [
                [['data' => 'OLD-IBAN']],
                [],
            ],
        ]);

        $info = $process->getInfo();

        $this->assertContains('Partner data updated!', $info['notifications'] ?? []);

        $queries = $info['queries'] ?? [];
        $inserted = false;
        foreach ($queries as $query) {
            if (strpos($query, 'INSERT INTO 0_bi_partners_data') !== false) {
                $inserted = true;
                $this->assertStringContainsString('NEW-IBAN', $query);
            }
        }
        $this->assertTrue($inserted, 'The partner data is written to the bi_partners_data table.');
    }
}
