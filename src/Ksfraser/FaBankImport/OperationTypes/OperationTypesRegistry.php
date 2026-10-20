<?php

namespace Ksfraser\FaBankImport\OperationTypes;

/**
 * @BABOK RTM: FR-013; file: OperationTypes/OperationTypesRegistry.php; coverage: docs/TestCases/ (file rollup)
 */
class OperationTypesRegistry
{
    private static ?self $instance = null;
    private array $types = [];

    /** @BABOK FR-013 — method: getInstance */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** @BABOK FR-013 — method: getTypes */
    public function getTypes(): array
    {
        return $this->types;
    }

    /** @BABOK FR-013 — method: addType */
    public function addType(string $key, string $label): void
    {
        $this->types[$key] = $label;
    }

    /** @BABOK FR-013 — method: getLabel */
    public function getLabel(string $key): ?string
    {
        return $this->types[$key] ?? null;
    }

    /** @BABOK FR-013 — method: isValid */
    public function isValid(string $key): bool
    {
        return isset($this->types[$key]);
    }
}
