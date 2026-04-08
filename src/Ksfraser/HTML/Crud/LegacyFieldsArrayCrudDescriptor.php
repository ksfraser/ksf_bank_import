<?php

namespace Ksfraser\HTML\Crud;

/**
 * Adapter that turns legacy `table_interface` metadata (`table_details`, `fields_array`)
 * into a CRUD descriptor usable by modern view/builders.
 *
 * Goal: reuse existing schema/field arrays without forcing a rewrite.
 */
class LegacyFieldsArrayCrudDescriptor
{
    /** @var array */
    private $tableDetails;

    /** @var array */
    private $fieldsArray;

    /** @var array */
    private $options;

    /**
     * @param array $tableDetails Legacy table_details
     * @param array $fieldsArray Legacy fields_array
     * @param array $options Optional overrides:
     *   - title (string)
     *   - listColumns (string[])
     *   - formFields (string[])
     *   - foreignKeys (array fieldName => foreignKeySpec)
     */
    public function __construct(array $tableDetails, array $fieldsArray, array $options = array())
    {
        $this->tableDetails = $tableDetails;
        $this->fieldsArray = $fieldsArray;
        $this->options = $options;
    }

    /**
     * Build a descriptor from a legacy table_interface-style object.
     *
     * @param object $legacy
     * @param array $options
     * @return self
     */
    public static function fromLegacy($legacy, array $options = array())
    {
        $tableDetails = array();
        $fieldsArray = array();

        if (is_object($legacy)) {
            if (isset($legacy->table_details) && is_array($legacy->table_details)) {
                $tableDetails = $legacy->table_details;
            }
            if (isset($legacy->fields_array) && is_array($legacy->fields_array)) {
                $fieldsArray = $legacy->fields_array;
            }
        }

        return new self($tableDetails, $fieldsArray, $options);
    }

    /** @return string */
    public function getTitle()
    {
        if (isset($this->options['title']) && $this->options['title'] !== '') {
            return (string) $this->options['title'];
        }

        $tableName = $this->getTableName();
        if ($tableName !== '') {
            return $tableName;
        }

        return 'CRUD';
    }

    /** @return string */
    public function getTableName()
    {
        if (isset($this->tableDetails['tablename'])) {
            return (string) $this->tableDetails['tablename'];
        }
        if (isset($this->tableDetails['table_name'])) {
            return (string) $this->tableDetails['table_name'];
        }
        return '';
    }

    /** @return string|null */
    public function getPrimaryKey()
    {
        if (isset($this->tableDetails['primarykey']) && $this->tableDetails['primarykey'] !== '') {
            return (string) $this->tableDetails['primarykey'];
        }
        return null;
    }

    /**
     * Returns field descriptors suitable for building a form.
     *
     * @return CrudField[]
     */
    public function getFields()
    {
        $foreignKeys = isset($this->options['foreignKeys']) && is_array($this->options['foreignKeys'])
            ? $this->options['foreignKeys']
            : array();

        $fields = array();
        foreach ($this->fieldsArray as $row) {
            if (!is_array($row) || !isset($row['name'])) {
                continue;
            }

            $name = (string) $row['name'];
            $label = isset($row['label']) && $row['label'] !== '' ? (string) $row['label'] : $name;
            $type = isset($row['type']) ? (string) $row['type'] : '';

            $readwrite = isset($row['readwrite']) ? (string) $row['readwrite'] : 'readwrite';
            $readOnly = ($readwrite === 'read' || $readwrite === 'readonly');

            $required = false;
            if (isset($row['null'])) {
                $required = (stripos((string) $row['null'], 'NOT NULL') !== false);
            }

            $defaultValue = null;
            if (array_key_exists('default', $row)) {
                $defaultValue = $row['default'];
            }

            $inputType = $this->inferInputType($name, $type);

            $foreignKeySpec = null;
            if (isset($foreignKeys[$name]) && is_array($foreignKeys[$name])) {
                $foreignKeySpec = $foreignKeys[$name];
                $inputType = 'select';
            }

            $fields[] = new CrudField($name, $label, $inputType, $required, $readOnly, $defaultValue, $foreignKeySpec);
        }

        if (isset($this->options['formFields']) && is_array($this->options['formFields'])) {
            $allowed = array_flip($this->options['formFields']);
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
        if (isset($this->options['listColumns']) && is_array($this->options['listColumns'])) {
            return array_values($this->options['listColumns']);
        }

        // Reasonable default: primary key + first few non-text columns.
        $cols = array();

        $pk = $this->getPrimaryKey();
        if ($pk !== null && $pk !== '') {
            $cols[] = $pk;
        }

        foreach ($this->fieldsArray as $row) {
            if (!is_array($row) || !isset($row['name'])) {
                continue;
            }

            $name = (string) $row['name'];
            if ($name === $pk) {
                continue;
            }

            $type = isset($row['type']) ? (string) $row['type'] : '';
            if ($this->isProbablyLargeText($type)) {
                continue;
            }

            $cols[] = $name;
            if (count($cols) >= 6) {
                break;
            }
        }

        return $cols;
    }

    /**
     * Best-effort inference for HTML input widgets.
     *
     * @param string $name
     * @param string $sqlType
     * @return string One of: text, textarea, number, checkbox, date, datetime, select
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
            // Heuristic: *_id often means FK and should be a select, but we require explicit FK mapping
            // to avoid guessing wrong. Keep it number by default.
            return 'number';
        }

        if (strpos($typeLower, 'double') !== false || strpos($typeLower, 'decimal') !== false || strpos($typeLower, 'float') !== false) {
            return 'number';
        }

        if ($this->isProbablyLargeText($typeLower)) {
            return 'textarea';
        }

        return 'text';
    }

    /**
     * @param string $sqlTypeLower
     * @return bool
     */
    private function isProbablyLargeText($sqlTypeLower)
    {
        $t = strtolower($sqlTypeLower);
        return (strpos($t, 'text') !== false || strpos($t, 'blob') !== false);
    }
}
