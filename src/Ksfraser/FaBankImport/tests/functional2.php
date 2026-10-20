class FunctionalCest
{
    /** @BABOK NFR-016 / FR-018 — method: _before */
    public function _before(AcceptanceTester $I)
    {
    }

    /** @BABOK NFR-016 / FR-018 — method: testHelloWorld */
    public function testHelloWorld(AcceptanceTester $I)
    {
        $I->amOnPage('/');
        $I->see('Hello World');
    }
}
