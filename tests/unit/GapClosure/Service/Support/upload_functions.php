<?php

declare(strict_types=1);

/*
 * The FileStorageService decides per promotion whether the statement is a
 * real upload: is_uploaded_file()/move_uploaded_file() are then used.
 * A pure promotion of a file that was not uploaded is the test fixture; the
 * real uploader functions never answer true for it, so the upload branch
 * of the store needs stand-ins.
 *
 * The stand-ins live in the service's own namespace, where the unqualified
 * calls resolve first. Paths can be marked as uploads through
 * $GLOBALS['__ksf_test_uploaded_paths']; without any marking both calls
 * delegate to the original functions.
 */
namespace Ksfraser\FaBankImport\Service {
    if (!function_exists('Ksfraser\FaBankImport\Service\is_uploaded_file')) {
        /**
         * @param string $filename Path of the file.
         * @return bool
         */
        function is_uploaded_file(string $filename): bool
        {
            $marked = $GLOBALS['__ksf_test_uploaded_paths'] ?? null;
            if (is_array($marked) && in_array($filename, $marked, true)) {
                return true;
            }

            return \is_uploaded_file($filename);
        }
    }

    if (!function_exists('Ksfraser\FaBankImport\Service\move_uploaded_file')) {
        /**
         * @param string $filename Path of the uploaded file.
         * @param string $destination Destination path.
         * @return bool
         */
        function move_uploaded_file(string $filename, string $destination): bool
        {
            if (isset($GLOBALS['__ksf_test_upload_move_fails'])) {
                return false;
            }

            return rename($filename, $destination);
        }
    }
}
