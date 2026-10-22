<?php

declare(strict_types=1);

/**
 * Tests for the module error handler.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Handlers
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\Handlers;

use Ksfraser\FaBankImport\Config\Config;
use Ksfraser\FaBankImport\Handlers\ErrorHandler;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\ProcessingTestCase;

require_once __DIR__ . '/../Support/ProcessingTestCase.php';

/**
 * ErrorHandler coverage.
 */
final class ErrorHandlerTest extends ProcessingTestCase
{
    /** @var string Directory the error log is written to. */
    private $logDir;

    /**
     * Point the module configuration at a writable log directory.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->logDir = sys_get_temp_dir() . '/ksf_bank_import_error_handler';

        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0777, true);
        }

        $config = Config::getInstance();
        $config->set('logging.path', $this->logDir);
        $config->set('app.debug', false);
    }

    /**
     * Restore the module configuration.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Config::resetInstance();

        parent::tearDown();
    }

    /**
     * An exception is logged and reported without details.
     *
     * @return void
     */
    public function test_an_exception_is_logged_and_reported(): void
    {
        $handler = new ErrorHandler();

        ob_start();
        $handler->handleException(new \RuntimeException('boom'));
        $output = ob_get_clean();

        $this->assertStringContainsString('An error occurred. Please try again or contact support.', $output);
        $this->assertStringNotContainsString('boom', $output);

        $log = file_get_contents($this->logDir . '/error.log');
        $this->assertStringContainsString('boom', $log);
        $this->assertStringContainsString('bank-import.ERROR', $log);
        $this->assertStringContainsString(__FILE__, $log);
    }

    /**
     * With debug output enabled the message and the trace are shown.
     *
     * @return void
     */
    public function test_debug_mode_shows_the_message_and_the_trace(): void
    {
        Config::getInstance()->set('app.debug', true);

        $handler = new ErrorHandler();

        ob_start();
        $handler->handleException(new \RuntimeException('boom'));
        $output = ob_get_clean();

        $this->assertStringContainsString("<div class='error'>", $output);
        $this->assertStringContainsString('boom', $output);
        $this->assertStringContainsString('<pre>', $output);
    }
}
