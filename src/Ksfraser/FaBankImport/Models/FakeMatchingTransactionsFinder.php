<?php
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Models;

/**
 * Test double matching-transactions finder.
 *
 * Serves a seeded match array regardless of the line item.
 *
 * @since 20260822
 */
class FakeMatchingTransactionsFinder implements MatchingTransactionsFinderInterface
{
    /** @var array */
    protected $matches;

    /**
     * Constructor.
     *
     * @param array $matches Match rows to serve.
     */
    public function __construct(array $matches = array())
    {
        $this->matches = $matches;
    }

    /**
     * @inheritDoc
     */
    public function findFor(object $lineItem): array
    {
        return $this->matches;
    }
}
