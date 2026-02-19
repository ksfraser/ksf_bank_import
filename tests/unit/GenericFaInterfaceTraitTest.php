<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\GenericInterface\GenericFaInterfaceTrait;

class GenericFaInterfaceTraitTest extends TestCase
{
    // Helper class to use the trait
    public function getTraitInstance()
    {
        return new class {
            use GenericFaInterfaceTrait;
            public $foo;
            public $bar;
            public $validated = false;
            public function validate_field($field, $value)
            {
                $this->validated = true;
                if ($field === 'foo' && $value === 'bad') {
                    return false;
                }
                return true;
            }
        };
    }

    public function testSetAndGet()
    {
        $obj = $this->getTraitInstance();
        $obj->set('foo', 123);
        $this->assertEquals(123, $obj->get('foo'));
    }

    public function testSetValidationPasses()
    {
        $obj = $this->getTraitInstance();
        $obj->set('bar', 'ok');
        $this->assertTrue($obj->validated);
        $this->assertEquals('ok', $obj->bar);
    }

    public function testSetValidationFails()
    {
        $this->expectException(Exception::class);
        $obj = $this->getTraitInstance();
        $obj->set('foo', 'bad');
    }

    public function testInsertDataAndArr2Obj()
    {
        $obj = $this->getTraitInstance();
        $data = ['foo' => 1, 'bar' => 2];
        $obj->arr2obj($data);
        $this->assertEquals(1, $obj->foo);
        $this->assertEquals(2, $obj->bar);
    }
}
