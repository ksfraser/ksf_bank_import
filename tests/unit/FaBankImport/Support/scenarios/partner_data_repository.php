<?php

declare(strict_types=1);

/**
 * Scenario: exercises the guard of
 * \Ksfraser\FaBankImport\Repository\DatabasePartnerDataRepository.
 *
 * The repository refuses to be built when FrontAccounting's TB_PREF
 * constant is missing. The FA mocks of vendor/ksfraser/famock always
 * define TB_PREF, so the guard can only be observed from a process that
 * does not load them.
 */

use Ksfraser\FaBankImport\Repository\DatabasePartnerDataRepository;

echo 'TB_PREF_DEFINED=' . (defined('TB_PREF') ? '1' : '0') . "\n";

try {
    new DatabasePartnerDataRepository();
    echo "NO_EXCEPTION\n";
} catch (\RuntimeException $exception) {
    echo 'EXCEPTION=' . $exception->getMessage() . "\n";
}
