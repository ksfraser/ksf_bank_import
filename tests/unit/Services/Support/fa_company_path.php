<?php

declare(strict_types=1);

/*
 * FrontAccounting provides company_path() to locate the company directory.
 * The module uses it through BankImportPathResolver::forCurrentCompany(), so
 * the unit tests need a stand-in for it.
 *
 * The returned path can be steered through $GLOBALS['__ksf_test_company_path'];
 * without it a directory below the system temp directory is used.
 */
if (!function_exists('company_path')) {
    /**
     * @param int $company Company id.
     * @return string
     */
    function company_path($company = 0)
    {
        if (!empty($GLOBALS['__ksf_test_company_path'])) {
            return rtrim((string) $GLOBALS['__ksf_test_company_path'], '/\\') . DIRECTORY_SEPARATOR;
        }

        return rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'fa_company_' . (int) $company . DIRECTORY_SEPARATOR;
    }
}
