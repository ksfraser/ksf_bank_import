<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :BiConfigHistorySchema [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for BiConfigHistorySchema.
 */
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Schema;

/**
 * Single-source-of-truth schema+UI descriptor for the `bi_config_history` entity.
 */
final class BiConfigHistorySchema
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
            'entity' => 'bi_config_history',
            'table' => 'bi_config_history',
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
                'old_value' => array(
                    'label' => 'Old Value',
                    'type' => 'text',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'new_value' => array(
                    'label' => 'New Value',
                    'type' => 'text',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'changed_at' => array(
                    'label' => 'Changed At',
                    'type' => 'timestamp',
                    'null' => 'NOT NULL',
                    'readwrite' => 'read',
                    'default' => 'CURRENT_TIMESTAMP',
                ),
                'changed_by' => array(
                    'label' => 'Changed By',
                    'type' => 'varchar(60)',
                    'null' => 'NOT NULL',
                    'readwrite' => 'readwrite',
                ),
                'change_reason' => array(
                    'label' => 'Reason',
                    'type' => 'text',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
            ),
            'ui' => array(
                'title' => 'Config History',
                'pageSize' => 50,
                'listColumns' => array('config_key', 'changed_at', 'changed_by'),
                'formFields' => array('config_key', 'old_value', 'new_value', 'changed_by', 'change_reason'),
                'tabs' => array(
                    array('title' => 'List', 'action' => 'list', 'form' => 'list_form', 'hidden' => false),
                ),
            ),
        );

        return self::$descriptor;
    }

}
