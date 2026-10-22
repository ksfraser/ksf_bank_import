<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Exceptions;

use Ksfraser\FaBankImport\Exceptions\UnauthorizedException;
use PHPUnit\Framework\TestCase;

final class UnauthorizedExceptionTest extends TestCase
{
    public function testItCarriesTheDefaultMessageAndCode(): void
    {
        $exception = new UnauthorizedException();

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertSame('Unauthorized access', $exception->getMessage());
        $this->assertSame(403, $exception->getCode());
    }

    public function testItAcceptsACustomMessage(): void
    {
        $exception = new UnauthorizedException('This section requires administrator privileges');

        $this->assertSame('This section requires administrator privileges', $exception->getMessage());
        $this->assertSame(403, $exception->getCode());
    }

    public function testItAcceptsACustomCode(): void
    {
        $exception = new UnauthorizedException('Nope', 401);

        $this->assertSame('Nope', $exception->getMessage());
        $this->assertSame(401, $exception->getCode());
    }
}
