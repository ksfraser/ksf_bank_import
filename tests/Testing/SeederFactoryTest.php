<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Testing;

use Ksfraser\FaBankImport\Testing\Seeder;
use Ksfraser\FaBankImport\Testing\SeederFactory;
use PHPUnit\Framework\TestCase;
use PDO;

/**
 * SeederFactoryTest - Test seeder factory registration and execution
 *
 * Tests the SeederFactory's ability to register, manage, and execute
 * seeders in a coordinated way for test fixture setup.
 *
 * @coversDefaultClass \Ksfraser\FaBankImport\Testing\SeederFactory
 */
class SeederFactoryTest extends TestCase
{
    private SeederFactory $factory;
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->factory = new SeederFactory($this->pdo);
    }

    /**
     * Test that factory can be instantiated
     *
     * @test
     * @covers ::__construct
     */
    public function testFactoryCanBeInstantiated(): void
    {
        $this->assertInstanceOf(SeederFactory::class, $this->factory);
    }

    /**
     * Test that seeders can be registered
     *
     * @test
     * @covers ::register
     */
    public function testSeederCanBeRegistered(): void
    {
        $seeder = $this->createMockSeeder('TestSeeder', 10);
        $result = $this->factory->register($seeder);

        // Should return self for fluent interface
        $this->assertSame($this->factory, $result);
    }

    /**
     * Test fluent interface allows chaining
     *
     * @test
     * @covers ::register
     */
    public function testFluentInterfaceAllowsChaining(): void
    {
        $seeder1 = $this->createMockSeeder('Seeder1', 10);
        $seeder2 = $this->createMockSeeder('Seeder2', 20);

        $result = $this->factory->register($seeder1)->register($seeder2);

        $this->assertSame($this->factory, $result);
    }

    /**
     * Test that has() returns false for unregistered seeder
     *
     * @test
     * @covers ::has
     */
    public function testHasReturnsFalseForUnregisteredSeeder(): void
    {
        $this->assertFalse($this->factory->has('NonexistentSeeder'));
    }

    /**
     * Test that has() returns true for registered seeder
     *
     * @test
     * @covers ::has
     */
    public function testHasReturnsTrueForRegisteredSeeder(): void
    {
        $seeder = $this->createMockSeeder('TestSeeder', 10);
        $this->factory->register($seeder);

        $this->assertTrue($this->factory->has('TestSeeder'));
    }

    /**
     * Test that list() returns empty array for no seeders
     *
     * @test
     * @covers ::list
     */
    public function testListReturnsEmptyForNoSeeders(): void
    {
        $list = $this->factory->list();

        $this->assertIsArray($list);
        $this->assertEmpty($list);
    }

    /**
     * Test that list() returns registered seeders
     *
     * @test
     * @covers ::list
     */
    public function testListReturnsRegisteredSeeders(): void
    {
        $seeder1 = $this->createMockSeeder('Seeder1', 10);
        $seeder2 = $this->createMockSeeder('Seeder2', 20);

        $this->factory->register($seeder1)->register($seeder2);

        $list = $this->factory->list();

        $this->assertCount(2, $list);
        $this->assertArrayHasKey('Seeder1', $list);
        $this->assertArrayHasKey('Seeder2', $list);
    }

    /**
     * Test that totalRecordCount() is zero for no seeders
     *
     * @test
     * @covers ::totalRecordCount
     */
    public function testTotalRecordCountZeroForNoSeeders(): void
    {
        $this->assertSame(0, $this->factory->totalRecordCount());
    }

    /**
     * Test that totalRecordCount() sums all seeders
     *
     * @test
     * @covers ::totalRecordCount
     */
    public function testTotalRecordCountSumsAllSeeders(): void
    {
        $seeder1 = $this->createMockSeeder('Seeder1', 10);
        $seeder2 = $this->createMockSeeder('Seeder2', 20);
        $seeder3 = $this->createMockSeeder('Seeder3', 15);

        $this->factory->register($seeder1)->register($seeder2)->register($seeder3);

        $this->assertSame(45, $this->factory->totalRecordCount());
    }

    /**
     * Test that run() throws exception for unregistered seeder
     *
     * @test
     * @covers ::run
     */
    public function testRunThrowsExceptionForUnregisteredSeeder(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Seeder not found: NonexistentSeeder');

        $this->factory->run('NonexistentSeeder');
    }

    /**
     * Test that run() executes and returns record count
     *
     * @test
     * @covers ::run
     */
    public function testRunExecutesSeederAndReturnsRecordCount(): void
    {
        $seeder = $this->createMockSeeder('TestSeeder', 42);
        $this->factory->register($seeder);

        $result = $this->factory->run('TestSeeder');

        $this->assertSame(42, $result);
    }

    /**
     * Test that run() wraps seeder exceptions
     *
     * @test
     * @covers ::run
     */
    public function testRunWrapsSeederExceptions(): void
    {
        $seeder = $this->getMockBuilder(Seeder::class)->getMock();
        $seeder->method('name')->willReturn('FailingSeeder');
        $seeder->method('description')->willReturn('Fails on seed');
        $seeder->method('recordCount')->willReturn(10);
        $seeder->method('seed')->willThrowException(new \Exception('Original error'));

        $this->factory->register($seeder);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Seeder FailingSeeder failed');

        $this->factory->run('FailingSeeder');
    }

    /**
     * Test that runAll() executes all seeders
     *
     * @test
     * @covers ::runAll
     */
    public function testRunAllExecutesAllSeeders(): void
    {
        $seeder1 = $this->createMockSeeder('Seeder1', 10);
        $seeder2 = $this->createMockSeeder('Seeder2', 20);

        $this->factory->register($seeder1)->register($seeder2);

        $results = $this->factory->runAll();

        $this->assertCount(2, $results);
        $this->assertSame(10, $results['Seeder1']);
        $this->assertSame(20, $results['Seeder2']);
    }

    /**
     * Test that runAll() returns empty array for no seeders
     *
     * @test
     * @covers ::runAll
     */
    public function testRunAllReturnsEmptyForNoSeeders(): void
    {
        $results = $this->factory->runAll();

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /**
     * Test that runAll() preserves seeder order
     *
     * @test
     * @covers ::runAll
     */
    public function testRunAllPreserveSeederOrder(): void
    {
        $seeder1 = $this->createMockSeeder('Alpha', 1);
        $seeder2 = $this->createMockSeeder('Beta', 2);
        $seeder3 = $this->createMockSeeder('Gamma', 3);

        $this->factory->register($seeder1)->register($seeder2)->register($seeder3);

        $results = $this->factory->runAll();

        $keys = array_keys($results);
        $this->assertSame(['Alpha', 'Beta', 'Gamma'], $keys);
    }

    /**
     * Test that runAll() stops on seeder failure
     *
     * @test
     * @covers ::runAll
     */
    public function testRunAllStopsOnSeederFailure(): void
    {
        $seeder1 = $this->createMockSeeder('Seeder1', 10);

        $seeder2 = $this->getMockBuilder(Seeder::class)->getMock();
        $seeder2->method('name')->willReturn('FailingSeeder');
        $seeder2->method('description')->willReturn('Fails');
        $seeder2->method('recordCount')->willReturn(20);
        $seeder2->method('seed')->willThrowException(new \Exception('Seed error'));

        $seeder3 = $this->createMockSeeder('Seeder3', 30);

        $this->factory->register($seeder1)->register($seeder2)->register($seeder3);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('FailingSeeder failed');

        $this->factory->runAll();
    }

    /**
     * Helper: Create a mock seeder
     */
    private function createMockSeeder(string $name, int $recordCount): Seeder
    {
        $seeder = $this->getMockBuilder(Seeder::class)->getMock();
        $seeder->method('name')->willReturn($name);
        $seeder->method('description')->willReturn("Seeder: {$name}");
        $seeder->method('recordCount')->willReturn($recordCount);
        $seeder->method('seed')->with($this->isInstanceOf(PDO::class))->willReturnCallback(function () {
            // Mock seed implementation does nothing
        });

        return $seeder;
    }
}
