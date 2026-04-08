<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Container;
use Ksfraser\FaBankImport\Services\TransactionService;
use Ksfraser\FaBankImport\Factories\TransactionTypeFactory;
use Ksfraser\FaBankImport\Repositories\TransactionRepository;

class ContainerTest extends TestCase
{
    private $container;

    protected function setUp(): void
    {
        $this->container = Container::getInstance();
    }

    public function testGetTransactionService()
    {
        $service = $this->container->getTransactionService();
        $this->assertInstanceOf(TransactionService::class, $service);
    }

    public function testGetTransactionTypeFactory()
    {
        $factory = $this->container->getTransactionTypeFactory();
        $this->assertInstanceOf(TransactionTypeFactory::class, $factory);
    }

    public function testGetTransactionRepository()
    {
        $repository = $this->container->getTransactionRepository();
        $this->assertInstanceOf(TransactionRepository::class, $repository);
    }

    public function testSingletonBehavior()
    {
        $container1 = Container::getInstance();
        $container2 = Container::getInstance();
        
        $this->assertSame($container1, $container2);
    }

    public function testServiceCaching()
    {
        $service1 = $this->container->getTransactionService();
        $service2 = $this->container->getTransactionService();
        
        $this->assertSame($service1, $service2);
    }
}