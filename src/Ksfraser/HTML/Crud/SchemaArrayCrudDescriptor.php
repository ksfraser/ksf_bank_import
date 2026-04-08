<?php

namespace Ksfraser\HTML\Crud;

/**
 * CRUD descriptor adapter for a single schema array.
 *
 * This is the "one file truth" path: a single schema descriptor contains
 * column + UI hints; this class normalizes it into CrudField objects.
 */
class SchemaArrayCrudDescriptor
{
    /** @var array */
    private $schema;

    /**
     * @param array $schema A schema descriptor array (see fa_classes schema examples)
     */
    public function __construct(array $schema)
    {
        $this->schema = $schema;
    }

    /** @return string */
    public function getTitle()
    {
        if (isset($this->schema['ui']['title']) && $this->schema['ui']['title'] !== '') {
            return (string) $this->schema['ui']['title'];
        }
        if (isset($this->schema['entity']) && $this->schema['entity'] !== '') {
            return (string) $this->schema['entity'];
        }
        return 'CRUD';
    }

    /** @return string */
    public function getTable()
    {
        return isset($this->schema['table']) ? (string) $this->schema['table'] : '';
    }

    /** @return string|null */
    public function getPrimaryKey()
    {
        if (isset($this->schema['primaryKey']) && $this->schema['primaryKey'] !== '') {
            return (string) $this->schema['primaryKey'];
        }
        return null;
    }

    /**
     * @return CrudField[]
     */
    public function getFields()
    {
        $fields = array();

        $relationships = isset($this->schema['relationships']) && is_array($this->schema['relationships'])
            ? $this->schema['relationships']
            : array();

        if (!isset($this->schema['fields']) || !is_array($this->schema['fields'])) {
            return $fields;
        }

        foreach ($this->schema['fields'] as $name => $field) {
            if (!is_array($field)) {
                $field = array();
            }

            $label = isset($field['label']) && $field['label'] !== '' ? (string) $field['label'] : (string) $name;
            $type = isset($field['type']) ? (string) $field['type'] : '';

            $readwrite = isset($field['readwrite']) ? (string) $field['readwrite'] : 'readwrite';
            $readOnly = ($readwrite === 'read' || $readwrite === 'readonly');

            $required = false;
            if (isset($field['null'])) {
                $required = (stripos((string) $field['null'], 'NOT NULL') !== false);
            }

            $defaultValue = null;
            if (array_key_exists('default', $field)) {
                $defaultValue = $field['default'];
            }

            $inputType = $this->inferInputType((string) $name, $type);

            $foreignKey = null;
            if (
                isset($relationships[$name])
                && is_array($relationships[$name])
                && isset($relationships[$name]['type'])
                && $relationships[$name]['type'] === 'fk'
            ) {
                $foreignKey = $relationships[$name];
                $inputType = 'select';
            }

            $fields[] = new CrudField((string) $name, $label, $inputType, $required, $readOnly, $defaultValue, $foreignKey);
        }

        if (isset($this->schema['ui']['formFields']) && is_array($this->schema['ui']['formFields'])) {
            $allowed = array_flip($this->schema['ui']['formFields']);
            $fields = array_values(array_filter($fields, function (CrudField $field) use ($allowed) {
                return isset($allowed[$field->name]);
            }));
        }

        return $fields;
    }

    /**
     * @return string[]
     */
    public function getListColumns()
    {
        if (isset($this->schema['ui']['listColumns']) && is_array($this->schema['ui']['listColumns'])) {
            return array_values($this->schema['ui']['listColumns']);
        }

        $pk = $this->getPrimaryKey();
        $cols = array();
        if ($pk !== null) {
            $cols[] = $pk;
        }

        if (isset($this->schema['fields']) && is_array($this->schema['fields'])) {
            foreach ($this->schema['fields'] as $name => $field) {
                if ($pk !== null && (string) $name === $pk) {
                    continue;
                }
                $type = is_array($field) && isset($field['type']) ? (string) $field['type'] : '';
                if (strpos(strtolower($type), 'text') !== false || strpos(strtolower($type), 'blob') !== false) {
                    continue;
                }
                $cols[] = (string) $name;
                if (count($cols) >= 6) {
                    break;
                }
            }
        }

        return $cols;
    }

    /**
     * @param string $name
     * @param string $sqlType
     * @return string
     */
    private function inferInputType($name, $sqlType)
    {
        $nameLower = strtolower($name);
        $typeLower = strtolower($sqlType);

        if ($nameLower === 'inactive') {
            return 'checkbox';
        }

        if (strpos($typeLower, 'bool') !== false || strpos($typeLower, 'tinyint(1)') !== false) {
            return 'checkbox';
        }

        if (strpos($typeLower, 'timestamp') !== false || strpos($typeLower, 'datetime') !== false) {
            return 'datetime';
        }

        if (strpos($typeLower, 'date') !== false) {
            return 'date';
        }

        if (preg_match('/\b(int|smallint|bigint)\b/', $typeLower)) {
            return 'number';
        }

        if (strpos($typeLower, 'double') !== false || strpos($typeLower, 'decimal') !== false || strpos($typeLower, 'float') !== false) {
            return 'number';
        }

        if (strpos($typeLower, 'text') !== false || strpos($typeLower, 'blob') !== false) {
            return 'textarea';
        }

        return 'text';
    }
}
