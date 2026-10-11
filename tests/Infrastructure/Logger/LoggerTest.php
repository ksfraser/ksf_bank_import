<?php

declare(strict_types=1);

namespace Tests\Ksfraser\FaBankImport\Infrastructure\Logger;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Infrastructure\Logger\NullLogger;
use Ksfraser\FaBankImport\Infrastructure\Logger\FileLogger;
use Ksfraser\FaBankImport\Contracts\Logger;

class LoggerTest extends TestCase
{
    /**
     * Test NullLogger does not throw on any level
     */
    public function testNullLoggerEmergency(): void
    {
        $logger = new NullLogger();
        $logger->emergency('Test message', ['context' => 'value']);
        
        $this->assertTrue(true); // No exception thrown
    }

    /**
     * Test NullLogger all methods
     */
    public function testNullLoggerAllLevels(): void
    {
        $logger = new NullLogger();
        
        $logger->emergency('emergency');
        $logger->alert('alert');
        $logger->critical('critical');
        $logger->error('error');
        $logger->warning('warning');
        $logger->notice('notice');
        $logger->info('info');
        $logger->debug('debug');
        $logger->log('custom', 'message');
        
        $this->assertTrue(true); // All completed without exception
    }

    /**
     * Test NullLogger implements Logger interface
     */
    public function testNullLoggerImplementsInterface(): void
    {
        $logger = new NullLogger();
        
        $this->assertInstanceOf(Logger::class, $logger);
    }

    /**
     * Test FileLogger creates file and writes logs
     */
    public function testFileLoggerCreatesAndWritesFile(): void
    {
        $tmpfile = tempnam(sys_get_temp_dir(), 'test_log_');
        unlink($tmpfile); // Remove the file, let logger create it
        
        try {
            $logger = new FileLogger($tmpfile);
            $logger->info('Test message');
            
            $this->assertTrue(file_exists($tmpfile));
            $content = file_get_contents($tmpfile);
            $this->assertStringContainsString('INFO', $content);
            $this->assertStringContainsString('Test message', $content);
        } finally {
            if (file_exists($tmpfile)) {
                unlink($tmpfile);
            }
        }
    }

    /**
     * Test FileLogger appends to existing file
     */
    public function testFileLoggerAppends(): void
    {
        $tmpfile = tempnam(sys_get_temp_dir(), 'test_log_');
        
        try {
            $logger = new FileLogger($tmpfile);
            $logger->info('First message');
            $logger->error('Second message');
            
            $content = file_get_contents($tmpfile);
            $this->assertStringContainsString('First message', $content);
            $this->assertStringContainsString('Second message', $content);
            
            // Ensure both are on separate lines
            $lines = explode(PHP_EOL, trim($content));
            $this->assertGreaterThanOrEqual(2, count($lines));
        } finally {
            if (file_exists($tmpfile)) {
                unlink($tmpfile);
            }
        }
    }

    /**
     * Test FileLogger includes timestamp
     */
    public function testFileLoggerIncludesTimestamp(): void
    {
        $tmpfile = tempnam(sys_get_temp_dir(), 'test_log_');
        
        try {
            $logger = new FileLogger($tmpfile);
            $logger->info('Test message');
            
            $content = file_get_contents($tmpfile);
            
            // Check for timestamp format YYYY-MM-DD HH:MM:SS
            $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $content);
        } finally {
            if (file_exists($tmpfile)) {
                unlink($tmpfile);
            }
        }
    }

    /**
     * Test FileLogger includes context as JSON
     */
    public function testFileLoggerIncludesContext(): void
    {
        $tmpfile = tempnam(sys_get_temp_dir(), 'test_log_');
        
        try {
            $logger = new FileLogger($tmpfile);
            $logger->info('Test message', ['user' => 'john', 'action' => 'login']);
            
            $content = file_get_contents($tmpfile);
            
            // Check that context is JSON encoded
            $this->assertStringContainsString('john', $content);
            $this->assertStringContainsString('action', $content);
        } finally {
            if (file_exists($tmpfile)) {
                unlink($tmpfile);
            }
        }
    }

    /**
     * Test FileLogger all log levels
     */
    public function testFileLoggerAllLevels(): void
    {
        $tmpfile = tempnam(sys_get_temp_dir(), 'test_log_');
        
        try {
            $logger = new FileLogger($tmpfile);
            $logger->emergency('emergency');
            $logger->alert('alert');
            $logger->critical('critical');
            $logger->error('error');
            $logger->warning('warning');
            $logger->notice('notice');
            $logger->info('info');
            $logger->debug('debug');
            
            $content = file_get_contents($tmpfile);
            
            $this->assertStringContainsString('EMERGENCY', $content);
            $this->assertStringContainsString('ALERT', $content);
            $this->assertStringContainsString('CRITICAL', $content);
            $this->assertStringContainsString('ERROR', $content);
            $this->assertStringContainsString('WARNING', $content);
            $this->assertStringContainsString('NOTICE', $content);
            $this->assertStringContainsString('INFO', $content);
            $this->assertStringContainsString('DEBUG', $content);
        } finally {
            if (file_exists($tmpfile)) {
                unlink($tmpfile);
            }
        }
    }

    /**
     * Test FileLogger creates directories if needed
     */
    public function testFileLoggerCreatesDirectories(): void
    {
        $tmpdir = sys_get_temp_dir() . '/test_logger_' . uniqid();
        $logfile = $tmpdir . '/logs/app.log';
        
        try {
            $logger = new FileLogger($logfile);
            $logger->info('Test');
            
            $this->assertTrue(is_dir($tmpdir));
            $this->assertTrue(file_exists($logfile));
        } finally {
            if (file_exists($logfile)) {
                unlink($logfile);
            }
            if (is_dir($tmpdir . '/logs')) {
                rmdir($tmpdir . '/logs');
            }
            if (is_dir($tmpdir)) {
                rmdir($tmpdir);
            }
        }
    }

    /**
     * Test FileLogger implements Logger interface
     */
    public function testFileLoggerImplementsInterface(): void
    {
        $tmpfile = tempnam(sys_get_temp_dir(), 'test_log_');
        
        try {
            $logger = new FileLogger($tmpfile);
            
            $this->assertInstanceOf(Logger::class, $logger);
        } finally {
            if (file_exists($tmpfile)) {
                unlink($tmpfile);
            }
        }
    }
}
