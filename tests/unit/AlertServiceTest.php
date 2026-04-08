<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\AlertService;
use Ksfraser\FaBankImport\Config\Config;
use org\bovigo\vfs\vfsStream;

class AlertServiceTest extends TestCase
{
    private $root;
    private $alertService;
    private $config;
    private $sentEmails = [];

    protected function setUp(): void
    {
        $this->root = vfsStream::setup('root');
        
        // Mock the mail function
        require_once __DIR__ . '/../../test_helpers/mail_mock.php';
        
        $this->config = $this->createMock(Config::class);
        $this->config->method('get')
            ->willReturnMap([
                ['alerts.enabled', false, true],
                ['alerts.email.to', null, 'admin@example.com'],
                ['alerts.email.from', null, 'monitoring@example.com'],
                ['alerts.email.subject_prefix', null, '[Test] '],
                ['monitoring.anomaly_threshold', null, 2.0],
                ['monitoring.slow_transaction_threshold', null, 1000],
                ['monitoring.high_memory_threshold', null, 10485760],
                ['logging.path', null, vfsStream::url('root')]
            ]);

        // Create test log file
        $logContent = json_encode([
            'datetime' => date('Y-m-d H:i:s'),
            'context' => [
                'name' => 'slow_metric',
                'time_elapsed' => 2.0,
                'memory_used' => 15728640
            ]
        ]) . "\n";
        vfsStream::newFile('performance_test.log')->at($this->root)->setContent($logContent);

        $this->alertService = AlertService::getInstance();
    }

    public function testCheckAndSendAlertsDetectsSlowTransactions(): void
    {
        $this->alertService->checkAndSendAlerts();
        
        $this->assertGreaterThan(0, count($GLOBALS['mock_mail_log']));
        $lastEmail = end($GLOBALS['mock_mail_log']);
        
        $this->assertStringContainsString('Slow Transactions Detected', $lastEmail['subject']);
        $this->assertStringContainsString('slow_metric', $lastEmail['message']);
        $this->assertEquals('admin@example.com', $lastEmail['to']);
    }

    public function testCheckAndSendAlertsDetectsHighMemoryUsage(): void
    {
        $this->alertService->checkAndSendAlerts();
        
        $highMemoryEmail = null;
        foreach ($GLOBALS['mock_mail_log'] as $email) {
            if (strpos($email['subject'], 'High Memory Usage') !== false) {
                $highMemoryEmail = $email;
                break;
            }
        }
        
        $this->assertNotNull($highMemoryEmail);
        $this->assertStringContainsString('15.00 MB', $highMemoryEmail['message']);
    }

    public function testCheckAndSendAlertsRespectsThresholds(): void
    {
        // Create a normal performance log entry
        $normalLogContent = json_encode([
            'datetime' => date('Y-m-d H:i:s'),
            'context' => [
                'name' => 'normal_metric',
                'time_elapsed' => 0.1,
                'memory_used' => 1048576
            ]
        ]) . "\n";
        vfsStream::newFile('performance_normal.log')->at($this->root)->setContent($normalLogContent);

        $initialEmailCount = count($GLOBALS['mock_mail_log']);
        $this->alertService->checkAndSendAlerts();
        $newEmailCount = count($GLOBALS['mock_mail_log']);

        // Only the slow and high memory alerts from the test log should be sent
        $this->assertEquals(2, $newEmailCount - $initialEmailCount);
    }

    protected function tearDown(): void
    {
        $GLOBALS['mock_mail_log'] = [];
    }
}