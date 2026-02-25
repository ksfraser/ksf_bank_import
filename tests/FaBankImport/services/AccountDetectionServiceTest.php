<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\AccountDetectionService;

class AccountDetectionServiceTest extends TestCase
{
    public function testDetectAccountsReturnsArray()
    {
        $service = new AccountDetectionService();
        $result = $service->detectAccounts([]);
        $this->assertIsArray($result);
    }
}
