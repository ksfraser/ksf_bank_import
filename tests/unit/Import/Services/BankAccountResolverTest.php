<?php

namespace Tests\Unit\Import\Services;

use Ksfraser\FaBankImport\Import\Services\BankAccountResolver;
use Ksfraser\FaBankImport\Import\Exceptions\BankAccountNotFoundException;
use PHPUnit\Framework\TestCase;

class BankAccountResolverTest extends TestCase
{
    private BankAccountResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new BankAccountResolver();
    }

    /**
     * Test empty account number throws exception.
     *
     * @test
     */
    public function testEmptyAccountNumberThrowsException(): void
    {
        $this->expectException(BankAccountNotFoundException::class);

        $this->resolver->resolveByAccountNumber('');
    }

    /**
     * Test invalid account ID throws exception.
     *
     * @test
     */
    public function testInvalidAccountIdThrowsException(): void
    {
        $this->expectException(BankAccountNotFoundException::class);

        $this->resolver->resolveByAccountId(-1);
    }

    /**
     * Test account validation with valid data.
     *
     * @test
     */
    public function testValidateWithValidAccount(): void
    {
        $account = ['id' => 1, 'account_code' => '1000', 'ACTIVE' => true];

        $result = $this->resolver->validate($account);

        $this->assertTrue($result->isSuccess());
    }

    /**
     * Test account validation with empty data.
     *
     * @test
     */
    public function testValidateWithEmptyData(): void
    {
        $result = $this->resolver->validate([]);

        $this->assertFalse($result->isSuccess());
    }

    /**
     * Test account validation with inactive account.
     *
     * @test
     */
    public function testValidateWithInactiveAccount(): void
    {
        $account = ['id' => 1, 'account_code' => '1000', 'ACTIVE' => false];

        $result = $this->resolver->validate($account);

        $this->assertFalse($result->isSuccess());
    }

    /**
     * Test comparing same accounts.
     *
     * @test
     */
    public function testCompareSameAccounts(): void
    {
        $account = ['id' => 100];

        $same = $this->resolver->areSame($account, 100);

        $this->assertTrue($same);
    }

    /**
     * Test comparing different accounts.
     *
     * @test
     */
    public function testCompareDifferentAccounts(): void
    {
        $same = $this->resolver->areSame(100, 200);

        $this->assertFalse($same);
    }

    /**
     * Test comparing with null values.
     *
     * @test
     */
    public function testCompareWithNullValues(): void
    {
        $account = ['id' => null];

        $same = $this->resolver->areSame($account, null);

        $this->assertFalse($same); // null !== null for this check
    }
}
