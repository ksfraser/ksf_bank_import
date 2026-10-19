<?php

/**
 * Test double for FrontAccounting's global $Refs object.
 *
 * FA's refs class hands out an increasing reference per transaction type:
 *
 *     $Refs->get_next(ST_CUSTPAYMENT)
 *
 * ReferenceNumberService accepts that generator through its constructor, so
 * the service can be exercised without a FrontAccounting environment.
 *
 * @package    Tests\Helpers
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251021
 */

declare(strict_types=1);

namespace Tests\Helpers;

/**
 * Stand-in for FA's refs class.
 */
class RefsDouble
{
    /** @var array<int, int> Last reference handed out per transaction type */
    private $counters = [];

    /** @var string Prefix of the generated references */
    private $prefix;

    /**
     * @param string $prefix Prefix of the generated references.
     */
    public function __construct(string $prefix = 'REF')
    {
        $this->prefix = $prefix;
    }

    /**
     * Hand out the next reference for a transaction type.
     *
     * @param int    $type   FA transaction type constant.
     * @param string $prefix Optional prefix override.
     *
     * @return string
     */
    public function get_next(int $type, string $prefix = ''): string
    {
        $this->counters[$type] = ($this->counters[$type] ?? 0) + 1;

        $usePrefix = $prefix !== '' ? $prefix : $this->prefix;

        return $usePrefix . '-' . $type . '-' . $this->counters[$type];
    }
}
