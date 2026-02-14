<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :BiUploadedFilesSchema [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for BiUploadedFilesSchema.
 */
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Schema;

/**
 * Single-source-of-truth schema+UI descriptor for the `bi_uploaded_files` entity.
 */
final class BiUploadedFilesSchema
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
            'entity' => 'bi_uploaded_files',
            'table' => 'bi_uploaded_files',
            'primaryKey' => 'id',
            'fields' => array(
                'id' => array(
                    'label' => 'ID',
                    'type' => 'int(11)',
                    'null' => 'NOT NULL',
                    'readwrite' => 'read',
                    'auto_increment' => true,
                ),
                'filename' => array(
                    'label' => 'Filename',
                    'type' => 'varchar(255)',
                    'null' => 'NOT NULL',
                    'readwrite' => 'readwrite',
                ),
                'original_filename' => array(
                    'label' => 'Original Filename',
                    'type' => 'varchar(255)',
                    'null' => 'NOT NULL',
                    'readwrite' => 'readwrite',
                ),
                'file_path' => array(
                    'label' => 'File Path',
                    'type' => 'varchar(512)',
                    'null' => 'NOT NULL',
                    'readwrite' => 'readwrite',
                ),
                'file_size' => array(
                    'label' => 'File Size',
                    'type' => 'int(11)',
                    'null' => 'NOT NULL',
                    'readwrite' => 'readwrite',
                ),
                'file_type' => array(
                    'label' => 'File Type',
                    'type' => 'varchar(100)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'upload_date' => array(
                    'label' => 'Upload Date',
                    'type' => 'datetime',
                    'null' => 'NOT NULL',
                    'readwrite' => 'readwrite',
                ),
                'upload_user' => array(
                    'label' => 'Upload User',
                    'type' => 'varchar(60)',
                    'null' => 'NOT NULL',
                    'readwrite' => 'readwrite',
                ),
                'parser_type' => array(
                    'label' => 'Parser Type',
                    'type' => 'varchar(50)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'bank_account_id' => array(
                    'label' => 'Bank Account ID',
                    'type' => 'int(11)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
                'statement_count' => array(
                    'label' => 'Statement Count',
                    'type' => 'int(11)',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                    'default' => '0',
                ),
                'notes' => array(
                    'label' => 'Notes',
                    'type' => 'text',
                    'null' => 'NULL',
                    'readwrite' => 'readwrite',
                ),
            ),
            'ui' => array(
                'title' => 'Uploaded Bank Files',
                'pageSize' => 25,
                'listColumns' => array('id', 'original_filename', 'upload_date', 'upload_user', 'parser_type', 'statement_count'),
                'formFields' => array('original_filename', 'file_type', 'upload_date', 'upload_user', 'parser_type', 'bank_account_id', 'notes'),
                'tabs' => array(
                    array('title' => 'List', 'action' => 'list', 'form' => 'list_form', 'hidden' => false),
                    array('title' => 'Add', 'action' => 'add', 'form' => 'add_form', 'hidden' => false),
                ),
            ),
        );

        return self::$descriptor;
    }

}
