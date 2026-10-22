<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\LineItems;

use Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\AttributesTraitConsumer;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/MoufValueUtils.php';
require_once FA_ROOT . '/src/Ksfraser/HTML/HtmlAttributesTrait.php';
require_once __DIR__ . '/Support/AttributesTraitConsumer.php';

/**
 * Coverage for the vendored Mouf HTML attribute trait.
 *
 * The trait is shipped in src/Ksfraser/HTML/HtmlAttributesTrait.php under the
 * Mouf\Html\Tags namespace. It is not referenced by any production class, so
 * it is exercised here through a small consumer that uses the trait.
 */
final class HtmlAttributesTraitTest extends TestCase
{
    /**
     * @var AttributesTraitConsumer
     */
    private $consumer;

    protected function setUp(): void
    {
        $this->consumer = new AttributesTraitConsumer();
    }

    public function testTheTraitIsAvailableUnderTheMoufNamespace(): void
    {
        $this->assertTrue(trait_exists('Mouf\Html\Tags\AttributesTrait'));
    }

    public function testSetAndGetClasses(): void
    {
        $this->consumer->setClasses(['alpha', 'beta']);

        $this->assertSame(['alpha' => 'alpha', 'beta' => 'beta'], $this->consumer->getClasses());
    }

    public function testGetClassesReturnsNullWhenNoClassWasSet(): void
    {
        $this->assertNull($this->consumer->getClasses());
    }

    public function testAddClassAddsASingleClass(): void
    {
        $this->consumer->addClass('alpha');

        $this->assertSame(['alpha' => 'alpha'], $this->consumer->getClasses());
    }

    public function testAddClassesAddsSeveralClasses(): void
    {
        $this->consumer->addClasses(['alpha', 'beta']);

        $this->assertSame(['alpha' => 'alpha', 'beta' => 'beta'], $this->consumer->getClasses());
    }

    public function testRemoveClassRemovesTheNamedClass(): void
    {
        $this->consumer->setClasses(['alpha', 'beta']);
        $this->consumer->removeClass('alpha');

        $this->assertSame(['beta' => 'beta'], $this->consumer->getClasses());
    }

    public function testSetAndGetId(): void
    {
        $this->assertNull($this->consumer->getId());

        $this->consumer->setId('the-id');

        $this->assertSame('the-id', $this->consumer->getId());
    }

    public function testSetAndGetContenteditable(): void
    {
        $this->assertNull($this->consumer->isContenteditable());

        $this->consumer->setContenteditable(true);

        $this->assertTrue($this->consumer->isContenteditable());
    }

    public function testSetAndGetDataAttributes(): void
    {
        $this->assertNull($this->consumer->getDataAttributes());

        $this->consumer->setDataAttributes(['role' => 'button']);

        $this->assertSame(['role' => 'button'], $this->consumer->getDataAttributes());
    }

    public function testAddDataAttributeAddsASingleEntry(): void
    {
        $this->consumer->addDataAttribute('role', 'button');

        $this->assertSame(['role' => 'button'], $this->consumer->getDataAttributes());
    }

    public function testRemoveDataAttributeRemovesTheNamedEntry(): void
    {
        $this->consumer->setDataAttributes(['role' => 'button', 'x' => 'y']);
        $this->consumer->removeDataAttribute('role');

        $this->assertSame(['x' => 'y'], $this->consumer->getDataAttributes());
    }

    public function testSetAndGetAccesskey(): void
    {
        $this->assertNull($this->consumer->getAccesskey());

        $this->consumer->setAccesskey('k');

        $this->assertSame('k', $this->consumer->getAccesskey());
    }

    public function testSetAndGetDir(): void
    {
        $this->assertNull($this->consumer->getDir());

        $this->consumer->setDir('ltr');

        $this->assertSame('ltr', $this->consumer->getDir());
    }

    public function testSetAndGetDraggable(): void
    {
        $this->assertNull($this->consumer->isDraggable());

        $this->consumer->setDraggable(true);

        $this->assertTrue($this->consumer->isDraggable());
    }

    public function testSetAndGetType(): void
    {
        $this->assertNull($this->consumer->getType());

        $this->consumer->setType('text');

        $this->assertSame('text', $this->consumer->getType());
    }

    public function testSetAndGetHidden(): void
    {
        $this->assertNull($this->consumer->isHidden());

        $this->consumer->setHidden(true);

        $this->assertTrue($this->consumer->isHidden());
    }

    public function testSetAndGetLang(): void
    {
        $this->assertNull($this->consumer->getLang());

        $this->consumer->setLang('en');

        $this->assertSame('en', $this->consumer->getLang());
    }

    public function testSetAndGetSpellcheck(): void
    {
        $this->assertNull($this->consumer->isSpellcheck());

        $this->consumer->setSpellcheck(false);

        $this->assertFalse($this->consumer->isSpellcheck());
    }

    public function testSetAndGetStyles(): void
    {
        $this->assertNull($this->consumer->getStyles());

        $this->consumer->setStyles(['color' => 'red']);

        $this->assertSame(['color' => 'red'], $this->consumer->getStyles());
    }

    public function testAddStyleAddsASingleStyle(): void
    {
        $this->consumer->addStyle('color', 'red');

        $this->assertSame(['color' => 'red'], $this->consumer->getStyles());
    }

    public function testRemoveStyleRemovesTheNamedStyle(): void
    {
        $this->consumer->setStyles(['color' => 'red', 'top' => '0']);
        $this->consumer->removeStyle('color');

        $this->assertSame(['top' => '0'], $this->consumer->getStyles());
    }

    public function testSetAndGetTitle(): void
    {
        $this->assertNull($this->consumer->getTitle());

        $this->consumer->setTitle('A title');

        $this->assertSame('A title', $this->consumer->getTitle());
    }

    public function testGetAttributesRendersPlainAttributes(): void
    {
        $this->consumer->setAttributeBag(['id' => 'x', 'title' => 'A "title"']);

        $this->assertSame('id="x" title="A &quot;title&quot;"', $this->consumer->renderAttributes());
    }

    public function testGetAttributesRendersAClassList(): void
    {
        $this->consumer->setAttributeBag(['class' => ['alpha', 'beta']]);

        $this->assertSame('class="alpha beta"', $this->consumer->renderAttributes());
    }

    public function testGetAttributesRendersAnInlineStyleList(): void
    {
        $this->consumer->setAttributeBag(['style' => ['color' => 'red', 'top' => '0']]);

        $this->assertSame('style="color:red;top:0;"', $this->consumer->renderAttributes());
    }

    public function testGetAttributesRendersDataAttributes(): void
    {
        $this->consumer->setAttributeBag(['data' => ['role' => 'button']]);

        $this->assertSame('data-role="button" ', $this->consumer->renderAttributes());
    }

    public function testGetAttributesRendersBooleanAttributes(): void
    {
        $this->consumer->setAttributeBag(['disabled' => true, 'readonly' => false]);

        $this->assertSame('disabled="true" readonly="false"', $this->consumer->renderAttributes());
    }

    public function testGetAttributesSkipsNullValues(): void
    {
        $this->consumer->setAttributeBag(['title' => null, 'id' => 'kept']);

        $this->assertSame('id="kept"', $this->consumer->renderAttributes());
    }

    public function testGetAttributesReturnsAnEmptyStringWithoutAttributes(): void
    {
        $this->assertSame('', $this->consumer->renderAttributes());
    }
}
