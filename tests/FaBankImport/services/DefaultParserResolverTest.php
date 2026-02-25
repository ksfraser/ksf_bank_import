<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\DefaultParserResolver;

class DefaultParserResolverTest extends TestCase
{
    public function testRequestedParserTakesPriority()
    {
        $parsers = ['QFX' => 'QFX', 'CSV' => 'CSV'];
        $resolver = new DefaultParserResolver();
        $result = $resolver->resolve($parsers, 'alice', 'CSV');
        $this->assertEquals('CSV', $result);
    }

    public function testUserDefaultParser()
    {
        $parsers = ['QFX' => 'QFX', 'CSV' => 'CSV'];
        $resolver = new DefaultParserResolver();
        // Simulate user default set in config
        \Ksfraser\FaBankImport\Config\ConfigService::getInstance()->set('user.bob.default_parser', 'CSV', 'bob');
        $result = $resolver->resolve($parsers, 'bob', null);
        $this->assertEquals('CSV', $result);
    }

    public function testCompanyDefaultParser()
    {
        $parsers = ['QFX' => 'QFX', 'CSV' => 'CSV'];
        $resolver = new DefaultParserResolver();
        \Ksfraser\FaBankImport\Config\ConfigService::getInstance()->set('company.default_parser', 'CSV', 'admin');
        $result = $resolver->resolve($parsers, null, null);
        $this->assertEquals('CSV', $result);
    }

    public function testFallbackToQFX()
    {
        $parsers = ['QFX' => 'QFX', 'CSV' => 'CSV'];
        $resolver = new DefaultParserResolver();
        $result = $resolver->resolve($parsers, null, null);
        $this->assertEquals('QFX', $result);
    }

    public function testFallbackToFirstParserIfNoQFX()
    {
        $parsers = ['CSV' => 'CSV', 'OFX' => 'OFX'];
        $resolver = new DefaultParserResolver();
        $result = $resolver->resolve($parsers, null, null);
        $this->assertEquals('CSV', $result);
    }

    public function testReturnsNullIfNoParsers()
    {
        $parsers = [];
        $resolver = new DefaultParserResolver();
        $result = $resolver->resolve($parsers, null, null);
        $this->assertNull($result);
    }

    public function testIgnoresInvalidRequestedParser()
    {
        $parsers = ['QFX' => 'QFX'];
        $resolver = new DefaultParserResolver();
        $result = $resolver->resolve($parsers, 'alice', 'INVALID');
        $this->assertEquals('QFX', $result);
    }
}
