<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Application\Config;

use Ksfraser\Application\Config\Config;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/ApplicationClasses.php';

final class ConfigTest extends TestCase
{
    /**
     * @var Config|null
     */
    private $original;

    protected function setUp(): void
    {
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationClasses::load();

        $this->original = Config::getInstance();
        Config::resetInstance();
    }

    protected function tearDown(): void
    {
        Config::setInstance($this->original);
    }

    public function testGetInstanceAlwaysReturnsTheSameInstance(): void
    {
        $first = Config::getInstance();
        $second = Config::getInstance();

        $this->assertSame($first, $second);
    }

    public function testGetReturnsTheNestedDatabaseSettings(): void
    {
        $config = Config::getInstance();

        $this->assertSame(getenv('DB_HOST') ?: 'localhost', $config->get('db.host'));
        $this->assertSame(getenv('DB_NAME') ?: 'test_db', $config->get('db.name'));
        $this->assertSame(getenv('DB_USER') ?: 'test_user', $config->get('db.user'));
        $this->assertSame(getenv('DB_PASS') ?: 'test_pass', $config->get('db.pass'));
    }

    public function testGetReturnsTheLoggingSettings(): void
    {
        $config = Config::getInstance();

        $this->assertTrue($config->get('logging.enabled'));
        $this->assertIsString($config->get('logging.path'));
    }

    public function testGetReturnsTheDefaultForAnUnknownKey(): void
    {
        $config = Config::getInstance();

        $this->assertNull($config->get('does.not.exist'));
        $this->assertSame('fallback', $config->get('does.not.exist', 'fallback'));
        $this->assertSame('fallback', $config->get('db.unknown', 'fallback'));
    }

    public function testGetReturnsTheDefaultWhenATraversedValueIsNotAnArray(): void
    {
        $config = Config::getInstance();

        $this->assertSame('fallback', $config->get('db.host.deeper', 'fallback'));
    }

    public function testSetStoresANestedValue(): void
    {
        $config = Config::getInstance();

        $config->set('alerts.enabled', true);

        $this->assertTrue($config->get('alerts.enabled'));
    }

    public function testSetCreatesTheMissingIntermediateLevels(): void
    {
        $config = Config::getInstance();

        $config->set('a.b.c', 'deep');

        $this->assertSame('deep', $config->get('a.b.c'));
        $this->assertIsArray($config->get('a.b'));
    }

    public function testSetReplacesANonArrayValueWithAnArray(): void
    {
        $config = Config::getInstance();

        $config->set('db', 'not-an-array');
        $config->set('db.host', 'db.example.com');

        $this->assertSame('db.example.com', $config->get('db.host'));
    }

    public function testSetOverwritesAnExistingValue(): void
    {
        $config = Config::getInstance();

        $config->set('logging.path', '/tmp/does-not-matter');

        $this->assertSame('/tmp/does-not-matter', $config->get('logging.path'));
    }

    public function testSetInstanceInstallsTheGivenInstance(): void
    {
        $replacement = Config::getInstance();
        Config::setInstance(null);

        $this->assertNotSame($replacement, Config::getInstance());

        Config::setInstance($replacement);

        $this->assertSame($replacement, Config::getInstance());
    }

    public function testResetInstanceDropsTheCurrentInstance(): void
    {
        $first = Config::getInstance();

        Config::resetInstance();
        $second = Config::getInstance();

        $this->assertNotSame($first, $second);
    }
}
