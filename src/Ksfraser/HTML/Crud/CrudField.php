<?php

namespace Ksfraser\HTML\Crud;

/**
 * Simple value object describing a CRUD field.
 *
 * Keep this PHP 7.3-compatible (no typed properties).
 */
class CrudField
{
    /** @var string */
    public $name;

    /** @var string */
    public $label;

    /** @var string */
    public $inputType;

    /** @var bool */
    public $required;

    /** @var bool */
    public $readOnly;

    /** @var mixed */
    public $defaultValue;

    /**
     * @var array|null Foreign key descriptor (optional).
     * Example: ['table' => 'suppliers', 'valueColumn' => 'supplier_id', 'labelColumn' => 'supp_name']
     */
    public $foreignKey;

    /**
     * @param string $name
     * @param string $label
     * @param string $inputType
     * @param bool $required
     * @param bool $readOnly
     * @param mixed $defaultValue
     * @param array|null $foreignKey
     */
    public function __construct(
        $name,
        $label,
        $inputType,
        $required = false,
        $readOnly = false,
        $defaultValue = null,
        $foreignKey = null
    ) {
        $this->name = (string) $name;
        $this->label = (string) $label;
        $this->inputType = (string) $inputType;
        $this->required = (bool) $required;
        $this->readOnly = (bool) $readOnly;
        $this->defaultValue = $defaultValue;
        $this->foreignKey = $foreignKey;
    }
}
