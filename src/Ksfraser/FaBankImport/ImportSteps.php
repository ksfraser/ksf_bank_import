<?php
namespace Ksfraser\FaBankImport;

/**
 * Shared workflow step constants for import state machine and forms.
 */
class ImportSteps
{
    public const UPLOAD_FORM = 'upload_form';
    public const PARSE_FILES = 'parse_files';
    public const DUPLICATE_RESOLUTION = 'duplicate_resolution';
    public const ACCOUNT_RESOLUTION = 'account_resolution';
    public const MAPPING_CONFIRMATION = 'mapping_confirmation';
    public const IMPORT = 'import';
    public const SUMMARY = 'summary';
    public const COMPLETE = 'complete';
}
