<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Config;

use Ksfraser\FaBankImport\Config\Config;
use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\FaBankImportTestSupport;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/FaBankImportTestSupport.php';

final class ConfigTest extends TestCase
{
    protected function tearDown(): void
    {
        FaBankImportTestSupport::restoreConfig();
    }

    public function testGetInstanceAlwaysReturnsTheSameInstance(): void
    {
        $first = Config::getInstance();
        $second = Config::getInstance();

        $this->assertSame($first, $second);
    }

    public function testTheDefaultSettingsArePresent(): void
    {
        $config = Config::getInstance();

        $this->assertIsArray($config->get('db'));
        $this->assertSame(getenv('DB_HOST') ?: 'localhost', $config->get('db.host'));
        $this->assertSame(getenv('DB_NAME') ?: 'fa_bank_import', $config->get('db.name'));
        $this->assertSame(getenv('DB_USER') ?: 'root', $config->get('db.user'));
        $this->assertTrue($config->get('logging.enabled'));
        $this->assertSame(['C', 'D', 'B'], $config->get('transaction.allowed_types'));
        $this->assertSame(1000000.00, $config->get('transaction.max_amount'));
        $this->assertFalse($config->get('upload.check_duplicates'));
        $this->assertSame(90, $config->get('upload.duplicate_window_days'));
        $this->assertSame('warn', $config->get('upload.duplicate_action'));
    }

    public function testGetReturnsTheDefaultForAnUnknownKey(): void
    {
        $config = Config::getInstance();

        $this->assertNull($config->get('does.not.exist'));
        $this->assertSame('fallback', $config->get('does.not.exist', 'fallback'));
        $this->assertSame('fallback', $config->get('unknown', 'fallback'));
    }

    public function testGetReturnsAWholeBranch(): void
    {
        $config = Config::getInstance();

        $upload = $config->get('upload');

        $this->assertIsArray($upload);
        $this->assertArrayHasKey('check_duplicates', $upload);
    }

    public function testSetStoresANestedValue(): void
    {
        $config = Config::getInstance();

        $config->set('custom.branch.value', 42);

        $this->assertSame(42, $config->get('custom.branch.value'));
        $this->assertSame(['value' => 42], $config->get('custom.branch'));
    }

    public function testSetReplacesANonArrayBranch(): void
    {
        $config = Config::getInstance();

        $config->set('custom.branch', 'scalar');
        $this->assertSame('scalar', $config->get('custom.branch'));

        $config->set('custom.branch.value', 42);
        $this->assertSame(42, $config->get('custom.branch.value'));
    }

    public function testSetOverwritesAnExistingValue(): void
    {
        $config = Config::getInstance();

        $config->set('logging.path', '/tmp/one');
        $config->set('logging.path', '/tmp/two');

        $this->assertSame('/tmp/two', $config->get('logging.path'));
    }

    public function testResetInstanceDropsTheSingleton(): void
    {
        $first = Config::getInstance();
        $first->set('custom.value', 'changed');

        Config::resetInstance();
        $second = Config::getInstance();

        $this->assertNotSame($first, $second);
        $this->assertNull($second->get('custom.value'));
    }

    public function testSetInstanceInstallsTheGivenInstance(): void
    {
        $original = Config::getInstance();

        $replacement = new \ReflectionClass(Config::class);
        $instance = $replacement->newInstanceWithoutConstructor();

        Config::setInstance($instance);
        $this->assertSame($instance, Config::getInstance());

        Config::setInstance($original);
        $this->assertSame($original, Config::getInstance());
    }
}
