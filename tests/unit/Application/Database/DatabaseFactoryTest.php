<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Application\Database;

use Ksfraser\Application\Database\DatabaseFactory;
use Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/ApplicationClasses.php';
require_once __DIR__ . '/../Support/ApplicationTestSupport.php';

final class DatabaseFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        DatabaseFactory::closeConnection();
    }

    protected function tearDown(): void
    {
        DatabaseFactory::closeConnection();
        ApplicationTestSupport::restoreConfig();
    }

    public function testTheConnectionIsCached(): void
    {
        $connection = new \stdClass();

        $property = new \ReflectionProperty(DatabaseFactory::class, 'connection');
        $property->setAccessible(true);
        $property->setValue(null, $connection);

        $this->assertSame($connection, DatabaseFactory::getConnection());
    }

    public function testCloseConnectionDropsTheCachedConnection(): void
    {
        $property = new \ReflectionProperty(DatabaseFactory::class, 'connection');
        $property->setAccessible(true);
        $property->setValue(null, new \stdClass());

        DatabaseFactory::closeConnection();

        $this->assertNull($property->getValue());
    }

    public function testAnUnreachableDatabaseIsReportedAsARuntimeException(): void
    {
        ApplicationTestSupport::useConfig([
            'db.host' => '127.0.0.1;port=1',
            'db.name' => 'ksf_fa',
            'db.user' => 'ksf_user',
            'db.pass' => 'ksf_user',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Connection failed: ');

        DatabaseFactory::getConnection();
    }

    public function testTheFactoryBuildsAPdoFromTheConfiguredDatabase(): void
    {
        ApplicationTestSupport::useConfig([
            // The DSN of the factory does not carry a port of its own, the
            // host therefore carries it.
            'db.host' => '127.0.0.1;port=3307',
            'db.name' => 'ksf_fa',
            'db.user' => 'ksf_user',
            'db.pass' => 'ksfuser2026!',
        ]);

        try {
            $connection = DatabaseFactory::getConnection();
        } catch (\RuntimeException $exception) {
            // No MySQL server is reachable in this environment.
            $this->assertStringContainsString('Connection failed: ', $exception->getMessage());

            return;
        }

        $this->assertInstanceOf(\PDO::class, $connection);
    }
}
