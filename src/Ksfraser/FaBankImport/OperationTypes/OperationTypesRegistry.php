<?php

namespace Ksfraser\FaBankImport\OperationTypes;

class OperationTypesRegistry
{
    private static ?self $instance = null;
    private array $types = [];

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    public function addType(string $key, string $label): void
    {
        $this->types[$key] = $label;
    }

    public function getLabel(string $key): ?string
    {
        return $this->types[$key] ?? null;
    }

    public function isValid(string $key): bool
    {
        return isset($this->types[$key]);
    }
}
