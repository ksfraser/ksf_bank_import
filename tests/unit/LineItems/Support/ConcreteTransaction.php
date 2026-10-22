<?php

declare(strict_types=1);

/**
 * A concrete transaction used to exercise the abstract transaction base class.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\LineItems\Support
 * @license    MIT
 */

namespace Ksfraser\FaBankImport\Tests\Unit\LineItems\Support;

use Ksfraser\FaBankImport\Models\AbstractTransaction;

/**
 * Concrete implementation of the abstract transaction base class.
 */
class ConcreteTransaction extends AbstractTransaction
{
    /**
     * @inheritDoc
     */
    public function getTransactionType(): string
    {
        return 'CONCRETE';
    }
}
