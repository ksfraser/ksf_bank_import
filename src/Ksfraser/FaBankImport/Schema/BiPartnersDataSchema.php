<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :BiPartnersDataSchema [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for BiPartnersDataSchema.
 */
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Schema;

/**
 * Single-source-of-truth schema+UI descriptor for the `bi_partners_data` entity.
 */
final class BiPartnersDataSchema
{
    use \Ksfraser\ModulesDAO\Schema\SchemaDescriptorHelpersTrait;

    /** @var array|null */
    private static $descriptor;

    public static function descriptor(): array
    {
        if (self::$descriptor !== null) {
            return self::$descriptor;
        }

        // Note: table has no explicit PRIMARY KEY in sql/update.sql.
        // We treat the unique key (partner_id, partner_detail_id, partner_type, data) as the logical primary key.
        self::$descriptor = array(
            'entity' => 'bi_partners_data',
            'table' => 'bi_partners_data',
            'primaryKey' => 'partner_id, partner_detail_id, partner_type, data',
            'fields' => array(
                'updated_ts' => array(
                    'label' => 'Updated',
                    'type' => 'timestamp',
                    'null' => 'NOT NULL',
                    'readwrite' => 'read',
                    'default' => 'CURRENT_TIMESTAMP',
                ),
                'partner_id' => array(
                    'label' => 'Partner ID',
                    'type' => 'int(11)',
                    'null' => 'NOT NULL',
                    'readwrite' => 'readwrite',
                ),
                'partner_detail_id' => array(
                    'label' => 'Partner Detail ID',
                    'type' => 'int(11)',
                    'null' => 'NOT NULL',
                    'readwrite' => 'readwrite',
                ),
                'partner_type' => array(
                    'label' => 'Partner Type',
                    'type' => 'int(11)',
                    'null' => 'NOT NULL',
                    'readwrite' => 'readwrite',
                ),
                'data' => array(
                    'label' => 'Keyword',
                    'type' => 'varchar(256)',
                    'null' => 'NOT NULL',
                    'readwrite' => 'readwrite',
                ),
                'occurrence_count' => array(
                    'label' => 'Occurrence Count',
                    'type' => 'int(11)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                    'default' => '1',
                ),
            ),
            'ui' => array(
                'title' => 'Partner Keywords',
                'pageSize' => 50,
                'listColumns' => array('partner_type', 'partner_id', 'partner_detail_id', 'data', 'occurrence_count'),
                'formFields' => array('partner_type', 'partner_id', 'partner_detail_id', 'data', 'occurrence_count'),
                'tabs' => array(
                    array('title' => 'List', 'action' => 'list', 'form' => 'list_form', 'hidden' => false),
                    array('title' => 'Add', 'action' => 'add', 'form' => 'add_form', 'hidden' => false),
                ),
            ),
        );

        return self::$descriptor;
    }

}
