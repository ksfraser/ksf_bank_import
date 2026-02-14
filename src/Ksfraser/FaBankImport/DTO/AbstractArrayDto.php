<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :AbstractArrayDto [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for AbstractArrayDto.
 */
declare(strict_types=1);

namespace Ksfraser\FaBankImport\DTO;

abstract class AbstractArrayDto
{
    /** @var array<string, mixed> */
    private $data;

    /**
     * @param array<string, mixed> $data
     */
    protected function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    protected function get(string $field, $default = null)
    {
        return $this->data[$field] ?? $default;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, string> $allowedFields
     * @return array<string, mixed>
     */
    protected static function filterAllowedFields(array $row, array $allowedFields): array
    {
        if (empty($allowedFields)) {
            return $row;
        }

        $allowed = array_fill_keys($allowedFields, true);
        return array_intersect_key($row, $allowed);
    }
}
