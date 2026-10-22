<?php

declare(strict_types=1);

/**
 * @package    Ksfraser\FaBankImport\Tests\Unit\Screens\Support
 * @license    MIT
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Screens\Support;

/**
 * Stand-in for FrontAccounting's $Ajax helper.
 *
 * The bank-import screens call $Ajax->activate('doc_tbl') to ask the page to
 * refresh a region. The real class needs a full FA session, so the screens
 * are driven against this recorder instead.
 */
final class ScreenAjax
{
    /**
     * @var array Regions that were asked to refresh.
     */
    private $activated = [];

    /**
     * Records the region refresh request.
     *
     * @param string $region Region id.
     *
     * @return void
     */
    public function activate($region): void
    {
        $this->activated[] = $region;
    }

    /**
     * The regions that were asked to refresh.
     *
     * @return array
     */
    public function getActivated(): array
    {
        return $this->activated;
    }
}
