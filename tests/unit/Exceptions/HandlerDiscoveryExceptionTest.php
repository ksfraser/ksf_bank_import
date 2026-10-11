<?php

/**
 * Handler Discovery Exception Test
 *
 * Tests for HandlerDiscoveryException class
 *
 * @package    Tests\Unit\Exceptions
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251021
 */

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Exceptions\HandlerDiscoveryException;

class HandlerDiscoveryExceptionTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_cannot_instantiate_exception(): void
    {
        $exception = HandlerDiscoveryException::cannotInstantiate('MyHandler');
        
        $this->assertInstanceOf(HandlerDiscoveryException::class, $exception);
        $this->assertStringContainsString('Cannot instantiate handler', $exception->getMessage());
        $this->assertStringContainsString('MyHandler', $exception->getMessage());
    }

    /**
     * @test
     */
    public function it_creates_invalid_constructor_exception(): void
    {
        $exception = HandlerDiscoveryException::invalidConstructor(
            'MyHandler',
            'wrong number of arguments'
        );
        
        $this->assertInstanceOf(HandlerDiscoveryException::class, $exception);
        $this->assertStringContainsString('invalid constructor', $exception->getMessage());
        $this->assertStringContainsString('MyHandler', $exception->getMessage());
        $this->assertStringContainsString('wrong number of arguments', $exception->getMessage());
    }

    /**
     * @test
     */
    public function it_creates_missing_dependency_exception(): void
    {
        $exception = HandlerDiscoveryException::missingDependency(
            'ErrorHandler',
            'Monolog\Logger'
        );
        
        $this->assertInstanceOf(HandlerDiscoveryException::class, $exception);
        $this->assertStringContainsString('missing dependency', $exception->getMessage());
        $this->assertStringContainsString('ErrorHandler', $exception->getMessage());
        $this->assertStringContainsString('Monolog\Logger', $exception->getMessage());
    }

    /**
     * @test
     */
    public function it_chains_previous_exception(): void
    {
        $previousException = new \Exception('Original error');
        
        $exception = HandlerDiscoveryException::cannotInstantiate(
            'MyHandler',
            $previousException
        );
        
        $this->assertSame($previousException, $exception->getPrevious());
    }

    /**
     * @test
     */
    public function it_extends_exception(): void
    {
        $exception = HandlerDiscoveryException::cannotInstantiate('MyHandler');
        
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    /**
     * @test
     */
    public function it_has_default_code_zero(): void
    {
        $exception = HandlerDiscoveryException::cannotInstantiate('MyHandler');
        
        $this->assertEquals(0, $exception->getCode());
    }

    /**
     * @test
     */
    public function it_uses_default_reason_for_invalid_constructor(): void
    {
        $exception = HandlerDiscoveryException::invalidConstructor('MyHandler');
        
        $this->assertStringContainsString('incompatible signature', $exception->getMessage());
    }
}
