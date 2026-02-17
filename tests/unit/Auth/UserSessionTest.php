<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use Ksfraser\FA\Auth\UserSession;

/**
 * Unit tests for UserSession utility.
 */
class UserSessionTest extends TestCase
{
    protected function setUp(): void
    {
        // Clear session before each test
        $_SESSION = [];
    }

    public function testGetCurrentUsernameReturnsUnknownWhenSessionEmpty(): void
    {
        $this->assertEquals('unknown', UserSession::getCurrentUsername());
    }

    public function testGetCurrentUsernameReturnsUsernameFromSessionObject(): void
    {
        $_SESSION['wa_current_user'] = (object)[
            'username' => 'testuser'
        ];

        $this->assertEquals('testuser', UserSession::getCurrentUsername());
    }

    public function testGetCurrentUsernameReturnsUnknownWhenUserNotObject(): void
    {
        $_SESSION['wa_current_user'] = 'not-an-object';

        $this->assertEquals('unknown', UserSession::getCurrentUsername());
    }

    public function testGetCurrentUsernameReturnsUnknownWhenUsernamePropertyMissing(): void
    {
        $_SESSION['wa_current_user'] = (object)[];

        $this->assertEquals('unknown', UserSession::getCurrentUsername());
    }
}
