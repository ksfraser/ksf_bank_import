<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Application\Services;

use Ksfraser\Application\Services\AlertService;
use Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport;
use Ksfraser\FaBankImport\Tests\Unit\Application\Support\IsolatedProcess;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/ApplicationClasses.php';
require_once __DIR__ . '/../Support/ApplicationTestSupport.php';
require_once __DIR__ . '/../Support/IsolatedProcess.php';

final class AlertServiceTest extends TestCase
{
    /**
     * @var string
     */
    private $logPath;

    /**
     * @var string
     */
    private $mailbox;

    /**
     * @var string
     */
    private $sendmail;

    protected function setUp(): void
    {
        $this->logPath = ApplicationTestSupport::createTempDir('ksf_alerts_');
        $this->mailbox = $this->logPath . '/mailbox.txt';
        $this->sendmail = $this->logPath . '/sendmail.sh';

        file_put_contents(
            $this->sendmail,
            "#!/bin/sh\ncat >> '" . $this->mailbox . "'\n"
        );
        chmod($this->sendmail, 0755);

        $entries = [];
        foreach ([0.1, 0.1, 5.0] as $index => $time) {
            $entries[] = json_encode([
                'datetime' => date('Y-m-d H:i:s'),
                'context' => [
                    'name' => 'process_transaction',
                    'time_elapsed' => $time,
                    'memory_used' => 20 * 1024 * 1024,
                ],
            ]);
        }
        file_put_contents(
            $this->logPath . '/performance_' . date('Ymd') . '.log',
            implode("\n", $entries) . "\n"
        );
    }

    protected function tearDown(): void
    {
        AlertService::resetInstance();
        ApplicationTestSupport::removeDir($this->logPath);
    }

    public function testGetInstanceAlwaysReturnsTheSameInstance(): void
    {
        $first = AlertService::getInstance();
        $second = AlertService::getInstance();

        $this->assertSame($first, $second);
    }

    public function testResetInstanceDropsTheCurrentInstance(): void
    {
        $first = AlertService::getInstance();

        AlertService::resetInstance();
        $second = AlertService::getInstance();

        $this->assertNotSame($first, $second);
    }

    public function testNoAlertIsSentWhenAlertsAreDisabled(): void
    {
        $process = (new IsolatedProcess())->run(
            'alert_service',
            ['disabled', $this->logPath],
            ['sendmail_path' => $this->sendmail . ' -t -i']
        );

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());
        $this->assertStringContainsString('ALERTS_DONE', $process->getStdout());
        $this->assertFileDoesNotExist($this->mailbox);

        $process->mergeCoverage($this);
    }

    public function testAnAnomalySlowAndHighMemoryAlertAreSent(): void
    {
        $process = (new IsolatedProcess())->run(
            'alert_service',
            ['enabled', $this->logPath],
            ['sendmail_path' => $this->sendmail . ' -t -i']
        );

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());
        $this->assertStringContainsString('ALERTS_DONE', $process->getStdout());
        $this->assertFileExists($this->mailbox);

        $mailbox = (string) file_get_contents($this->mailbox);
        $this->assertStringContainsString('[ALERT] Performance Anomalies Detected', $mailbox);
        $this->assertStringContainsString('[ALERT] Slow Transactions Detected', $mailbox);
        $this->assertStringContainsString('[ALERT] High Memory Usage Detected', $mailbox);
        $this->assertStringContainsString('ops@example.com', $mailbox);
        $this->assertStringContainsString('process_transaction', $mailbox);

        $process->mergeCoverage($this);
    }
}
