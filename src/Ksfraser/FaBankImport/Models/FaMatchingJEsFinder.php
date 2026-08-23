<?php
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Models;

/**
 * FA-backed matching-transactions finder wrapping legacy MatchingJEs.
 *
 * The legacy require chain (MatchingJEs -> ksf_modules_common class.fa_gl.php)
 * only works in a real FrontAccounting + module-common environment; guarded
 * here so compat/test mode falls back cleanly.
 *
 * @since 20260822
 */
class FaMatchingJEsFinder implements MatchingTransactionsFinderInterface
{
    /**
     * @inheritDoc
     */
    public function findFor(object $lineItem): array
    {
        if (!static::legacyAvailable()) {
            return array();
        }

        if (!class_exists('Ksfraser\\FaBankImport\\models\\MatchingJEs', false)) {
            require_once __DIR__ . '/../models/MatchingJEs.php';
        }

        $match = new \Ksfraser\FaBankImport\models\MatchingJEs($lineItem);
        $result = $match->getMatchArr();
        return is_array($result) ? $result : array();
    }

    /**
     * Whether the legacy dependency tree is loadable in this environment.
     *
     * @return bool
     */
    public static function legacyAvailable(): bool
    {
        return !defined('KSF_TEST_COMPAT');
    }
}
