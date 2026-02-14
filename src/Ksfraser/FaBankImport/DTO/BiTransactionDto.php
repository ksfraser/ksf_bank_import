<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\DTO;

use Ksfraser\FaBankImport\Schema\BiTransactionsSchema;
use Ksfraser\FaBankImport\TransactionDC\TransactionDCRules;

final class BiTransactionDto
    extends AbstractArrayDto
{
    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        if (isset($row['title']) && !isset($row['transactionTitle'])) {
            $row['transactionTitle'] = $row['title'];
        }

        return new self(self::filterAllowedFields($row, BiTransactionsSchema::fieldNames()));
    }

    public function getId(): int
    {
        return (int) $this->get('id', 0);
    }

    public function getTitle(): string
    {
        // Some code uses title, the BI table uses transactionTitle
        $title = $this->get('title', $this->get('transactionTitle', ''));
        return (string) $title;
    }

    public function getTransactionDC(): string
    {
        $rawValue = (string) $this->get('transactionDC', '');
        if (TransactionDCRules::isAllowed($rawValue)) {
            return TransactionDCRules::normalize($rawValue);
        }

        return TransactionDCRules::resolve(null);
    }
}
