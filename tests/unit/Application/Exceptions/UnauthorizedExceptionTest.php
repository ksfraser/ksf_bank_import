<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Application\Exceptions;

use Ksfraser\Application\Exceptions\UnauthorizedException;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/ApplicationClasses.php';

final class UnauthorizedExceptionTest extends TestCase
{
    protected function setUp(): void
    {
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationClasses::load();
    }

    public function testItIsAnException(): void
    {
        $exception = new UnauthorizedException();

        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testDefaultMessageAndCode(): void
    {
        $exception = new UnauthorizedException();

        $this->assertSame('Unauthorized access', $exception->getMessage());
        $this->assertSame(403, $exception->getCode());
    }

    public function testCustomMessageAndCode(): void
    {
        $exception = new UnauthorizedException('This section requires administrator privileges', 401);

        $this->assertSame('This section requires administrator privileges', $exception->getMessage());
        $this->assertSame(401, $exception->getCode());
    }

    public function testItCanBeThrownAndCaught(): void
    {
        try {
            throw new UnauthorizedException('nope');
        } catch (UnauthorizedException $exception) {
            $this->assertSame('nope', $exception->getMessage());
            $this->assertSame(403, $exception->getCode());
        }
    }
}
