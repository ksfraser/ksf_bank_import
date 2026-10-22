<?php

declare(strict_types=1);

/**
 * Partner type double used to exercise the abstract partner type base class.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Support
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\Support;

use Ksfraser\PartnerTypes\AbstractPartnerType;

/**
 * A partner type whose short code and constant name can be chosen.
 */
final class TestablePartnerType extends AbstractPartnerType
{
    /** @var string Short code. */
    private $shortCode;

    /** @var string Name of the matching constant. */
    private $constantName;

    /**
     * Constructor.
     *
     * @param string $shortCode    Short code of the partner type.
     * @param string $constantName Name of the matching constant.
     */
    public function __construct(string $shortCode = 'ZZ', string $constantName = 'MATCHED')
    {
        $this->shortCode = $shortCode;
        $this->constantName = $constantName;
    }

    /**
     * Two character short code.
     *
     * @return string
     */
    public function getShortCode(): string
    {
        return $this->shortCode;
    }

    /**
     * Human readable label.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return 'Testable';
    }

    /**
     * Name of the matching constant.
     *
     * @return string
     */
    public function getConstantName(): string
    {
        return $this->constantName;
    }

    /**
     * Expose the protected short code validation.
     *
     * @return void
     */
    public function callValidateShortCode(): void
    {
        $this->validateShortCode();
    }
}
