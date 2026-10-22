<?php

declare(strict_types=1);

/**
 * Reference generator double used in place of FrontAccounting's $Refs global.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Support
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\Support;

/**
 * Returns predictable reference numbers.
 */
final class FakeReferenceGenerator
{
    /** @var int Counter used to build unique references. */
    private $counter = 0;

    /** @var string Prefix of every generated reference. */
    private $prefix;

    /**
     * Constructor.
     *
     * @param string $prefix Prefix of every generated reference.
     */
    public function __construct(string $prefix = 'REF')
    {
        $this->prefix = $prefix;
    }

    /**
     * Produce the next reference for a transaction type.
     *
     * @param int $transactionType Transaction type constant.
     *
     * @return string
     */
    public function get_next($transactionType): string
    {
        $this->counter++;

        return $this->prefix . '-' . $transactionType . '-' . $this->counter;
    }
}
