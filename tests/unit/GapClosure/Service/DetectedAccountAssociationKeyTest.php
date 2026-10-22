<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\GapClosure\Service;

use Ksfraser\FaBankImport\Service\DetectedAccountAssociationKey;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for the account association key builder.
 *
 * @see \Ksfraser\FaBankImport\Service\DetectedAccountAssociationKey
 */
final class DetectedAccountAssociationKeyTest extends TestCase
{
    /**
     * @return void
     */
    public function testAKeyIsBuiltForADetectedAccount(): void
    {
        $key = DetectedAccountAssociationKey::forDetectedAccount('Chequing 12345');

        $this->assertStringStartsWith('acct_assoc.Chequing12345.', $key);
        $this->assertLessThanOrEqual(100, strlen($key));
    }

    /**
     * @return void
     */
    public function testTheKeyIsStableForTheSameAccount(): void
    {
        $first = DetectedAccountAssociationKey::forDetectedAccount('Chequing 12345');
        $second = DetectedAccountAssociationKey::forDetectedAccount('Chequing 12345');

        $this->assertSame($first, $second);
    }

    /**
     * @return void
     */
    public function testAnEmptyAccountFallsBackToTheGenericName(): void
    {
        $key = DetectedAccountAssociationKey::forDetectedAccount('');

        $this->assertStringStartsWith('acct_assoc.acct.', $key);
    }

    /**
     * @return void
     */
    public function testALongAccountIsTruncated(): void
    {
        $key = DetectedAccountAssociationKey::forDetectedAccount(str_repeat('a', 200));

        $this->assertSame(100, strlen($key));
        $this->assertStringStartsWith('acct_assoc.' . str_repeat('a', 80) . '.', $key);
    }

    /**
     * @return void
     */
    public function testTheAccountIsSanitised(): void
    {
        $key = DetectedAccountAssociationKey::forDetectedAccount('Acme Ltd. (#42)');

        $this->assertStringStartsWith('acct_assoc.AcmeLtd___42_.', $key);
    }

    /**
     * @return void
     */
    public function testTheAccountIsNormalisedWhenTheNormalisationFails(): void
    {
        $limit = (string) ini_get('pcre.backtrack_limit');
        ini_set('pcre.backtrack_limit', '1');

        try {
            $key = DetectedAccountAssociationKey::forDetectedAccount('Chequing 12345');
        } finally {
            ini_set('pcre.backtrack_limit', $limit);
        }

        // The normalisation failed, so the fallback name is used.
        $this->assertStringStartsWith('acct_assoc.acct.', $key);
    }
}
