<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :BiConfigSchema [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for BiConfigSchema.
 */
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Schema;

/**
 * Single-source-of-truth schema+UI descriptor for the `bi_config` entity.
 */
final class BiConfigSchema
{
    use \Ksfraser\ModulesDAO\Schema\SchemaDescriptorHelpersTrait;

    /** @var array|null */
    private static $descriptor;

    public static function descriptor(): array
    {
        if (self::$descriptor !== null) {
            return self::$descriptor;
        }

        self::$descriptor = array(
            'entity' => 'bi_config',
            'table' => 'bi_config',
            'primaryKey' => 'id',
            'fields' => array(
                'id' => array(
                    'label' => 'ID',
                    'type' => 'int(11)',
                    'null' => 'NOT NULL',
                    'readwrite' => 'read',
                    'auto_increment' => true,
                ),
                'config_key' => array(
                    'label' => 'Key',
                    'type' => 'varchar(100)',
                    'null' => 'NOT NULL',
                    'readwrite' => 'readwrite',
                ),
                'config_value' => array(
                    'label' => 'Value',
                    'type' => 'text',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'config_type' => array(
                    'label' => 'Type',
                    'type' => 'varchar(20)',
                    'null' => 'NOT NULL',
                    'readwrite' => 'readwrite',
                    'default' => 'string',
                ),
                'description' => array(
                    'label' => 'Description',
                    'type' => 'text',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'category' => array(
                    'label' => 'Category',
                    'type' => 'varchar(50)',
                    'null' => 'NOT NULL',
                    'readwrite' => 'readwrite',
                    'default' => 'general',
                ),
                'is_system' => array(
                    'label' => 'System',
                    'type' => 'tinyint(1)',
                    'null' => 'NOT NULL',
                    'readwrite' => 'readwrite',
                    'default' => '0',
                ),
                'updated_at' => array(
                    'label' => 'Updated At',
                    'type' => 'timestamp',
                    'null' => 'NOT NULL',
                    'readwrite' => 'read',
                    'default' => 'CURRENT_TIMESTAMP',
                ),
                'updated_by' => array(
                    'label' => 'Updated By',
                    'type' => 'varchar(60)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
            ),
            'ui' => array(
                'title' => 'Bank Import Config',
                'pageSize' => 50,
                'listColumns' => array('config_key', 'config_value', 'config_type', 'category', 'is_system', 'updated_at'),
                'formFields' => array('config_key', 'config_value', 'config_type', 'description', 'category', 'is_system'),
                'tabs' => array(
                    array('title' => 'List', 'action' => 'list', 'form' => 'list_form', 'hidden' => false),
                    array('title' => 'Add', 'action' => 'add', 'form' => 'add_form', 'hidden' => false),
                ),
            ),
        );

        return self::$descriptor;
    }

}
