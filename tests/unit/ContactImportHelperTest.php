<?php
use PHPUnit\Framework\TestCase;

class ContactImportHelperTest extends TestCase
{
    public function test_no_contact_returns_null()
    {
        $db = null;
        $smt = new stdClass();
        $t = new stdClass();

        $result = \Ksfraser\FaBankImport\Import\ContactImportHelper::attachContactIdFromParserTransaction($db, $smt, $t);

        $this->assertNull($result);
    }
}
