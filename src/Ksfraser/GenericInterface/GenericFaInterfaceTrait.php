<?php
namespace Ksfraser\GenericInterface;

trait GenericFaInterfaceTrait
{
    /** @var array<int, array<string, mixed>> */
    public $fields_array = [];

    /** @var string */
    public $company_prefix = '';

    /** @var string */
    public $iam = '';

    /** @var array<string, mixed> */
    public $table_details = [];

    public function set($field, $value = null, $enforce = true)
    {
        $this->$field = $value;
        return true;
    }

    public function get($field)
    {
        return $this->$field ?? null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insert_data(array $data): bool
    {
        return true;
    }

    /**
     * @param array<string, mixed> $row
     */
    public function arr2obj(array $row): bool
    {
        foreach ($row as $key => $value) {
            $this->$key = $value;
        }
        return true;
    }
}
