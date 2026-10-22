<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Config;

use Ksfraser\FaBankImport\Config\ConfigService;
use Ksfraser\FaBankImport\Repository\ConfigRepositoryInterface;
use Ksfraser\FaBankImport\Repository\DatabaseConfigRepository;
use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\FakeConfigRepository;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/FaBankImportStubs.php';

final class ConfigServiceTest extends TestCase
{
    /**
     * @var FakeConfigRepository
     */
    private $repository;

    protected function setUp(): void
    {
        $this->repository = new FakeConfigRepository([
            'upload.check_duplicates' => true,
            'upload.duplicate_window_days' => 30,
            'security.max_attempts' => 5,
        ]);

        $this->resetService();
    }

    protected function tearDown(): void
    {
        $this->resetService();
    }

    /**
     * Drops the configuration service singleton.
     *
     * @return void
     */
    private function resetService(): void
    {
        $property = new \ReflectionProperty(ConfigService::class, 'instance');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    public function testGetInstanceAlwaysReturnsTheSameInstance(): void
    {
        $first = ConfigService::getInstance();
        $second = ConfigService::getInstance();

        $this->assertSame($first, $second);
    }

    public function testGetInstanceUsesTheDatabaseRepositoryByDefault(): void
    {
        $service = ConfigService::getInstance();

        $this->assertInstanceOf(DatabaseConfigRepository::class, $service->getRepository());
    }

    public function testCreateBuildsAnInstanceWithTheGivenRepository(): void
    {
        $service = ConfigService::create($this->repository);

        $this->assertInstanceOf(ConfigService::class, $service);
        $this->assertSame($this->repository, $service->getRepository());
    }

    public function testGetReadsFromTheRepository(): void
    {
        $service = ConfigService::create($this->repository);

        $this->assertTrue($service->get('upload.check_duplicates'));
        $this->assertSame('fallback', $service->get('does.not.exist', 'fallback'));
    }

    public function testSetWritesToTheRepository(): void
    {
        $service = ConfigService::create($this->repository);

        $result = $service->set('upload.check_duplicates', false, 'admin', 'because');

        $this->assertTrue($result);
        $this->assertFalse($service->get('upload.check_duplicates'));

        $history = $service->getHistory('upload.check_duplicates');
        $this->assertCount(1, $history);
        $this->assertSame('admin', $history[0]['username']);
        $this->assertSame('because', $history[0]['reason']);
    }

    public function testGetByCategoryReadsFromTheRepository(): void
    {
        $service = ConfigService::create($this->repository);

        $values = $service->getByCategory('upload');

        $this->assertSame(
            ['upload.check_duplicates' => true, 'upload.duplicate_window_days' => 30],
            $values
        );
    }

    public function testGetAllReadsFromTheRepository(): void
    {
        $service = ConfigService::create($this->repository);

        $this->assertSame($this->repository->getAll(), $service->getAll());
    }

    public function testHasAsksTheRepository(): void
    {
        $service = ConfigService::create($this->repository);

        $this->assertTrue($service->has('upload.check_duplicates'));
        $this->assertFalse($service->has('does.not.exist'));
    }

    public function testGetHistoryReadsFromTheRepository(): void
    {
        $service = ConfigService::create($this->repository);

        $service->set('upload.check_duplicates', false);

        $this->assertCount(1, $service->getHistory());
        $this->assertCount(1, $service->getHistory('upload.check_duplicates'));
        $this->assertCount(0, $service->getHistory('security.max_attempts'));
    }

    public function testTheRepositoryExposesTheDocumentedContract(): void
    {
        $this->assertInstanceOf(ConfigRepositoryInterface::class, $this->repository);
    }
}
