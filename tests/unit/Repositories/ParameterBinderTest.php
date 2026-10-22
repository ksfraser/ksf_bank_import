<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Repositories;

use InvalidArgumentException;
use Ksfraser\FaBankImport\Repository\ParameterBinder;
use PHPUnit\Framework\TestCase;

/**
 * The binder resolves the "?" placeholders that FrontAccounting cannot bind.
 *
 * @see \Ksfraser\FaBankImport\Repository\ParameterBinder
 */
final class ParameterBinderTest extends TestCase
{
    /**
     * @return void
     */
    public function testItLeavesSqlWithoutParametersUntouched(): void
    {
        $binder = new ParameterBinder();

        $this->assertSame(
            'SELECT * FROM `0_bi_partners_data`',
            $binder->bind('SELECT * FROM `0_bi_partners_data`')
        );
    }

    /**
     * @return void
     */
    public function testItResolvesPositionalPlaceholders(): void
    {
        $binder = new ParameterBinder();

        $this->assertSame(
            "SELECT * FROM t WHERE partner_id = 1 AND partner_type = 2",
            $binder->bind('SELECT * FROM t WHERE partner_id = ? AND partner_type = ?', [1, 2])
        );
    }

    /**
     * @return void
     */
    public function testItQuotesStringsAndLeavesNumbersAlone(): void
    {
        $binder = new ParameterBinder();

        $this->assertSame(
            "WHERE a = 'shoppers' AND b = 5 AND c = 1.5",
            $binder->bind('WHERE a = ? AND b = ? AND c = ?', ['shoppers', 5, 1.5])
        );
    }

    /**
     * @return void
     */
    public function testItEscapesQuotesInValues(): void
    {
        $binder = new ParameterBinder();

        $this->assertSame(
            "WHERE name = 'O\\'Brien'",
            $binder->bind('WHERE name = ?', ["O'Brien"])
        );
    }

    /**
     * @return void
     */
    public function testItRendersNullAndBooleans(): void
    {
        $binder = new ParameterBinder();

        $this->assertSame(
            'WHERE a = NULL AND b = 1 AND c = 0',
            $binder->bind('WHERE a = ? AND b = ? AND c = ?', [null, true, false])
        );
    }

    /**
     * @return void
     */
    public function testItResolvesNamedPlaceholders(): void
    {
        $binder = new ParameterBinder();

        $this->assertSame(
            "WHERE id = 7 AND name = 'x'",
            $binder->bind('WHERE id = :id AND name = :name', ['id' => 7, 'name' => 'x'])
        );
    }

    /**
     * A value that looks like a regex backreference must not be expanded.
     *
     * @return void
     */
    public function testItDoesNotExpandBackreferencesInValues(): void
    {
        $binder = new ParameterBinder();

        $this->assertSame(
            "WHERE memo = '$0 and \\\\1'",
            $binder->bind('WHERE memo = ?', ['$0 and \\1'])
        );
    }

    /**
     * @return void
     */
    public function testItRefusesToBindMoreValuesThanPlaceholders(): void
    {
        $binder = new ParameterBinder();

        $this->expectException(InvalidArgumentException::class);

        $binder->bind('WHERE id = ?', [1, 2]);
    }

    /**
     * @return void
     */
    public function testItRefusesToBindFewerValuesThanPlaceholders(): void
    {
        $binder = new ParameterBinder();

        $this->expectException(InvalidArgumentException::class);

        $binder->bind('WHERE id = ? AND type = ?', [1]);
    }
}
