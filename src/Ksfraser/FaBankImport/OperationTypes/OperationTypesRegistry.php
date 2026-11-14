<?php

namespace Ksfraser\FaBankImport\OperationTypes;

/**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001

 */
class OperationTypesRegistry
{
    private static ?self $instance = null;
    private array $types = [];

    /** * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
*/
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
*/
    public function getTypes(): array
    {
        return $this->types;
    }

    /** * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
*/
    public function addType(string $key, string $label): void
    {
        $this->types[$key] = $label;
    }

    /** * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
*/
    public function getLabel(string $key): ?string
    {
        return $this->types[$key] ?? null;
    }

    /** * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
*/
    public function isValid(string $key): bool
    {
        return isset($this->types[$key]);
    }
}
