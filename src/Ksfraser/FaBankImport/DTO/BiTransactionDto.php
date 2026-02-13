<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\DTO;

use Ksfraser\FaBankImport\Config\Config;
use Ksfraser\FaBankImport\Schema\BiTransactionsSchema;

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
        $value = strtoupper(trim((string) $this->get('transactionDC', '')));
        if ($value !== '') {
            return $value;
        }

        $config = Config::getInstance();
        $configuredDefault = strtoupper(trim((string) $config->get('transaction.default_dc', 'D')));
        $allowedTypes = $config->get('transaction.allowed_types', ['C', 'D', 'B']);

        if (is_array($allowedTypes)) {
            $allowedTypes = array_map(
                static function ($type): string {
                    return strtoupper(trim((string) $type));
                },
                $allowedTypes
            );

            if (in_array($configuredDefault, $allowedTypes, true)) {
                return $configuredDefault;
            }
        }

        return 'D';
    }
}
