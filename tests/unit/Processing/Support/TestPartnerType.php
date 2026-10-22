<?php

declare(strict_types=1);

/**
 * Configurable partner type used to exercise the abstract transaction handler.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Support
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\Support;

use Ksfraser\PartnerTypes\PartnerTypeInterface;

/**
 * A partner type whose short code can be chosen by the test.
 */
final class TestPartnerType implements PartnerTypeInterface
{
    /** @var string Short code. */
    private $shortCode;

    /**
     * Constructor.
     *
     * @param string $shortCode Short code of the partner type.
     */
    public function __construct(string $shortCode = 'ZZ')
    {
        $this->shortCode = $shortCode;
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
        return 'Test Type ' . $this->shortCode;
    }

    /**
     * Name of the matching constant.
     *
     * @return string
     */
    public function getConstantName(): string
    {
        return 'TEST_TYPE';
    }

    /**
     * Sort priority.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 100;
    }

    /**
     * Optional description.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return 'Partner type used by the transaction processing tests.';
    }

    /**
     * View class name.
     *
     * @return string
     */
    public function getViewClassName(): string
    {
        return 'TestPartnerTypeView';
    }

    /**
     * Strategy method name.
     *
     * @return string
     */
    public function getStrategyMethodName(): string
    {
        return 'displayTestType';
    }
}
