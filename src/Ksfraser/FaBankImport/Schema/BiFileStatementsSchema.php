<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Schema;

/**
 * Single-source-of-truth schema+UI descriptor for the `bi_file_statements` entity.
 */
final class BiFileStatementsSchema
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
            'entity' => 'bi_file_statements',
            'table' => 'bi_file_statements',
            'primaryKey' => 'file_id, statement_id',
            'fields' => array(
                'file_id' => array(
                    'label' => 'File ID',
                    'type' => 'int(11)',
                    'null' => 'NOT NULL',
                    'readwrite' => 'readwrite',
                ),
                'statement_id' => array(
                    'label' => 'Statement ID',
                    'type' => 'int(11)',
                    'null' => 'NOT NULL',
                    'readwrite' => 'readwrite',
                ),
            ),
            'ui' => array(
                'title' => 'File Statements',
                'pageSize' => 50,
                'listColumns' => array('file_id', 'statement_id'),
                'formFields' => array('file_id', 'statement_id'),
            ),
            'relationships' => array(
                'file_id' => array('type' => 'fk', 'target' => 'bi_uploaded_files', 'valueColumn' => 'id', 'labelColumn' => 'original_filename'),
                'statement_id' => array('type' => 'fk', 'target' => 'bi_statements', 'valueColumn' => 'id', 'labelColumn' => 'statementId'),
            ),
        );

        return self::$descriptor;
    }

}
