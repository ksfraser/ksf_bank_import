<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\AlertService;
use Ksfraser\FaBankImport\Config\Config;
use Ksfraser\FaBankImport\Tests\Unit\Services\Support\FaDbFake;

require_once __DIR__ . '/Support/fa_db_stubs_services.php';

// The repository ships a namespaced mail() mock for the services namespace.
require_once __DIR__ . '/../../../test_helpers/mail_mock.php';

/**
 * Behavioural tests for the alert service.
 */
final class AlertServiceCoverageTest extends TestCase
{
    /** @var FaDbFake */
    private $fake;

    /** @var string */
    private $logDir;

    /** @var mixed */
    private $originalLogPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fake = FaDbFake::activate();
        $GLOBALS['mock_mail_log'] = [];

        $this->logDir = sys_get_temp_dir() . '/ksf_bank_import_alerts_' . uniqid('', true);
        mkdir($this->logDir, 0777, true);

        $config = Config::getInstance();
        $this->originalLogPath = $config->get('logging.path');
        $config->set('logging.path', $this->logDir);
        $config->set('alerts.enabled', true);
        $config->set('alerts.email.subject_prefix', '[KSF]');
        $config->set('alerts.email.to', 'ops@example.com');
        $config->set('alerts.email.from', 'fa@example.com');
        $config->set('monitoring.anomaly_threshold', 1.0);
        $config->set('monitoring.slow_transaction_threshold', 100000);
        $config->set('monitoring.high_memory_threshold', 10 * 1024 * 1024);

        AlertService::resetInstance();
    }

    protected function tearDown(): void
    {
        AlertService::resetInstance();

        $config = Config::getInstance();
        $config->set('logging.path', $this->originalLogPath);
        $config->set('alerts.enabled', false);

        FaDbFake::deactivate();
        $GLOBALS['mock_mail_log'] = [];

        foreach (glob($this->logDir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_dir($this->logDir)) {
            rmdir($this->logDir);
        }

        parent::tearDown();
    }

    public function testGetInstanceReturnsTheSameServiceUntilItIsReset(): void
    {
        $first = AlertService::getInstance();
        $second = AlertService::getInstance();

        $this->assertSame($first, $second);

        AlertService::resetInstance();

        $this->assertNotSame($first, AlertService::getInstance());
    }

    public function testNoMailIsSentWhenAlertsAreDisabled(): void
    {
        Config::getInstance()->set('alerts.enabled', false);
        $this->writeMetrics([['name' => 'import', 'time_elapsed' => 1.0, 'memory_used' => 100]]);

        AlertService::getInstance()->checkAndSendAlerts();

        $this->assertSame([], $this->sentMails());
    }

    public function testAnAnomalyTriggersAnAnomalyMail(): void
    {
        $this->writeMetrics([
            ['name' => 'import', 'time_elapsed' => 1.0, 'memory_used' => 100],
            ['name' => 'import', 'time_elapsed' => 3.0, 'memory_used' => 100],
        ]);

        AlertService::getInstance()->checkAndSendAlerts();

        $mails = $this->sentMails();
        $this->assertCount(1, $mails);
        $mail = $mails[0];

        $this->assertSame('[KSF] Performance Anomalies Detected', $mail['subject']);
        $this->assertSame('ops@example.com', $mail['to']);
        $this->assertStringContainsString('From: fa@example.com', $mail['headers']);
        $this->assertStringContainsString('Metric: import', $mail['message']);
    }

    public function testASlowMetricTriggersASlowTransactionMail(): void
    {
        Config::getInstance()->set('monitoring.slow_transaction_threshold', 1000);
        $this->writeMetrics([
            ['name' => 'import', 'time_elapsed' => 2.0, 'memory_used' => 100],
            ['name' => 'import', 'time_elapsed' => 2.0, 'memory_used' => 100],
        ]);

        AlertService::getInstance()->checkAndSendAlerts();

        $mails = $this->sentMails();
        $this->assertCount(1, $mails);
        $this->assertSame('[KSF] Slow Transactions Detected', $mails[0]['subject']);
        $this->assertStringContainsString('Slow transactions detected for import', $mails[0]['message']);
    }

    public function testHighMemoryUsageTriggersAHighMemoryMail(): void
    {
        $this->writeMetrics([
            ['name' => 'import', 'time_elapsed' => 0.001, 'memory_used' => 20 * 1024 * 1024],
        ]);

        AlertService::getInstance()->checkAndSendAlerts();

        $mails = $this->sentMails();
        $this->assertCount(1, $mails);
        $this->assertSame('[KSF] High Memory Usage Detected', $mails[0]['subject']);
        $this->assertStringContainsString('High memory usage detected for import', $mails[0]['message']);
    }

    public function testSteadyMetricsTriggerNoMailAtAll(): void
    {
        $this->writeMetrics([
            ['name' => 'import', 'time_elapsed' => 0.001, 'memory_used' => 100],
        ]);

        AlertService::getInstance()->checkAndSendAlerts();

        $this->assertSame([], $this->sentMails());
    }

    /**
     * The mails captured by the repository's namespaced mail() mock.
     *
     * @return array<int,array<string,string>>
     */
    private function sentMails(): array
    {
        return $GLOBALS['mock_mail_log'] ?? [];
    }

    /**
     * @param array<int,array{name:string,time_elapsed:float,memory_used:int}> $entries
     */
    private function writeMetrics(array $entries): void
    {
        $content = '';
        foreach ($entries as $entry) {
            $content .= json_encode([
                'datetime' => date('Y-m-d H:i:s', time() - 60),
                'context' => $entry,
            ]) . "\n";
        }

        file_put_contents($this->logDir . '/performance_alerts.log', $content);
    }
}
