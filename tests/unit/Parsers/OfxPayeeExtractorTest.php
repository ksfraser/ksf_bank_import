<?php

declare(strict_types=1);

/**
 * PAYEE / CCACCTFROM extraction from raw OFX content.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Parsers
 * @license    MIT
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Parsers;

use PHPUnit\Framework\TestCase;
use ofx_payee_extractor;

require_once __DIR__ . '/../../../includes/ofx_payee_extractor.php';

/**
 * @BABOK FR-IMP-005, FR-001 | BR-IMP-001 | UAT-IMP-001
 */
final class OfxPayeeExtractorTest extends TestCase
{
    /**
     * SGML payee children have no closing tags, so the next transaction tag
     * ends the aggregate.
     */
    public function testPayeeAggregateIsReadInSgmlForm(): void
    {
        $content = '<STMTTRN><TRNTYPE>DEBIT<FITID>ABC123'
            . '<PAYEE><NAME>SAMPLE PAYEE<ADDR1>123 FAKE ST<ADDR2>UNIT 4'
            . '<CITY>CALGARY<STATE>AB<POSTALCODE>T2P 0A1<COUNTRY>CANADA'
            . '<PHONE>5550100<EMAIL>payee@example.com'
            . '<MEMO>IGNORED</STMTTRN>';

        $payees = (new ofx_payee_extractor())->extractPayees($content);

        $this->assertArrayHasKey('ABC123', $payees);
        $this->assertSame('SAMPLE PAYEE', $payees['ABC123']['merchant']);
        $this->assertSame('123 FAKE ST', $payees['ABC123']['address1']);
        $this->assertSame('UNIT 4', $payees['ABC123']['address2']);
        $this->assertSame('CALGARY', $payees['ABC123']['city']);
        $this->assertSame('AB', $payees['ABC123']['province']);
        $this->assertSame('T2P 0A1', $payees['ABC123']['postalcode']);
        $this->assertSame('CANADA', $payees['ABC123']['country']);
        $this->assertArrayNotHasKey('memo', $payees['ABC123']);
    }

    /**
     * XML payee blocks close explicitly and do not leak into the next one.
     */
    public function testPayeeIsJoinedByFitidInXmlForm(): void
    {
        $content = '<STMTTRN><FITID>ONE<PAYEE><NAME>FIRST</NAME><CITY>RED DEER</CITY>'
            . '</PAYEE><MEMO>M1</MEMO></STMTTRN>'
            . '<STMTTRN><FITID>TWO<PAYEE><NAME>SECOND</NAME><CITY>CALGARY</CITY>'
            . '</PAYEE><MEMO>M2</MEMO></STMTTRN>';

        $payees = (new ofx_payee_extractor())->extractPayees($content);

        $this->assertSame('FIRST', $payees['ONE']['merchant']);
        $this->assertSame('RED DEER', $payees['ONE']['city']);
        $this->assertSame('SECOND', $payees['TWO']['merchant']);
        $this->assertSame('CALGARY', $payees['TWO']['city']);
    }

    /**
     * A transaction without PAYEE contributes nothing.
     */
    public function testTransactionWithoutPayeeIsSkipped(): void
    {
        $content = '<STMTTRN><FITID>PLAIN<NAME>STORE<MEMO>M</STMTTRN>';

        $this->assertSame(array(), (new ofx_payee_extractor())->extractPayees($content));
    }

    /**
     * CCACCTFROM accounts are returned only for credit-card statements.
     */
    public function testCreditCardAccountIdsAreExtracted(): void
    {
        $extractor = new ofx_payee_extractor();

        $creditCard = '<CREDITCARDMSGSRSV1><CCSTMTTRNRS><CCSTMTRS><CCACCTFROM>'
            . '<ACCTID>************2245<ACCTTYPE>CREDITLINE</CCACCTFROM>';
        $this->assertSame(
            array('************2245' => '************2245'),
            $extractor->extractCreditCardAccountIds($creditCard)
        );

        $bank = '<BANKMSGSRSV1><STMTRS><BANKACCTFROM><ACCTID>7143425</BANKACCTFROM>';
        $this->assertSame(array(), $extractor->extractCreditCardAccountIds($bank));
    }

    /**
     * Payee fields are copied, addresses composed, card set only for a CC.
     */
    public function testPayeeFieldsAreAppliedToTheTransaction(): void
    {
        $extractor = new ofx_payee_extractor();
        $trz = new \stdClass();
        $trz->city = 'OLD';

        $extractor->applyTo(
            $trz,
            array(
                'merchant' => 'SAMPLE PAYEE',
                'address1' => '123 FAKE ST',
                'address2' => 'UNIT 4',
                'city' => 'CALGARY',
                'province' => 'AB',
                'postalcode' => 'T2P 0A1',
                'country' => 'CANADA',
            ),
            '************2245',
            array('************2245' => '************2245')
        );

        $this->assertSame('CALGARY', $trz->city);
        $this->assertSame('AB', $trz->province);
        $this->assertSame('T2P 0A1', $trz->postalcode);
        $this->assertSame('CANADA', $trz->country);
        $this->assertSame('123 FAKE ST, UNIT 4', $trz->address);
        $this->assertSame('************2245', $trz->cardnumber);
    }

    /**
     * A partial PAYEE does not erase fields already on the transaction.
     */
    public function testPartialPayeeLeavesOtherFieldsAlone(): void
    {
        $extractor = new ofx_payee_extractor();
        $trz = new \stdClass();
        $trz->city = 'OLD';
        $trz->cardnumber = '';

        $extractor->applyTo($trz, array('merchant' => 'ONLY NAME'), '7143425', array());

        $this->assertSame('OLD', $trz->city);
        $this->assertSame('', $trz->cardnumber);
    }
}
