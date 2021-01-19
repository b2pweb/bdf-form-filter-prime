<?php

namespace Bdf\Form\Filter;

use Bdf\Form\Aggregate\Collection\ChildrenCollection;
use Bdf\Form\Aggregate\Form;
use Bdf\Form\Child\Child;
use Bdf\Form\Child\ChildBuilder;
use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Leaf\StringElementBuilder;
use Bdf\Form\PropertyAccess\ExtractorInterface;
use Bdf\Form\PropertyAccess\HydratorInterface;
use Bdf\Prime\Entity\Criteria as PrimeCriteria;
use Bdf\Prime\Query\Expression\Like;
use PHPUnit\Framework\TestCase;

class FilterChildBuilderTest extends TestCase
{
    /**
     * @var FilterChildBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->builder = new FilterChildBuilder(new ChildBuilder('foo', new StringElementBuilder()));
    }

    /**
     *
     */
    public function test_delegated_methods()
    {
        $builder = new FilterChildBuilder($inner = $this->createMock(ChildBuilderInterfaceWithFactory::class));

        $inner->expects($this->once())->method('hydrator')->with($h = $this->createMock(HydratorInterface::class));
        $this->assertSame($builder, $builder->hydrator($h));

        $inner->expects($this->once())->method('extractor')->with($e = $this->createMock(ExtractorInterface::class));
        $this->assertSame($builder, $builder->extractor($e));

        $inner->expects($this->once())->method('filter')->with(function() {}, false);
        $this->assertSame($builder, $builder->filter(function() {}, false));

        $inner->expects($this->once())->method('default')->with('foo');
        $this->assertSame($builder, $builder->default('foo'));

        $inner->expects($this->once())->method('depends')->with('foo', 'bar');
        $this->assertSame($builder, $builder->depends('foo', 'bar'));

        $inner->expects($this->once())->method('childFactory')->with(function() {});
        $this->assertSame($builder, $builder->childFactory(function() {}));

        $child = $this->builder->length(['min' => 3])->buildChild()->setParent(new Form(new ChildrenCollection()));
        $this->assertFalse($child->element()->submit('a')->valid());
        $this->assertTrue($child->element()->submit('aaa')->valid());
    }

    /**
     *
     */
    public function test_delegated_methods_default_parameters()
    {
        $builder = new FilterChildBuilder($inner = $this->createMock(ChildBuilderInterfaceWithFactory::class));

        $inner->expects($this->once())->method('filter')->with(function() {}, true);
        $this->assertSame($builder, $builder->filter(function() {}));
    }

    /**
     *
     */
    public function test_default()
    {
        $child = $this->builder->buildChild();

        $this->assertInstanceOf(Child::class, $child);
        $this->assertInstanceOf(StringElement::class, $child->element());
        $this->assertEquals('foo', $child->name());

        $child->submit(['foo' => 'bar']);

        $c = new PrimeCriteria();
        $child->fill($c);

        $this->assertFalse(isset($c['foo']));
    }

    /**
     *
     */
    public function test_criterion_without_parameters()
    {
        $child = $this->builder->criterion()->buildChild();

        $this->assertInstanceOf(Child::class, $child);
        $this->assertInstanceOf(StringElement::class, $child->element());
        $this->assertEquals('foo', $child->name());

        $child = $child->setParent(new Form(new ChildrenCollection()));
        $child->submit(['foo' => 'bar']);

        $c = new PrimeCriteria();
        $child->fill($c);

        $this->assertEquals(new PrimeCriteria(['foo' => 'bar']), $c);
    }

    /**
     *
     */
    public function test_criterion_with_attribute_parameter()
    {
        $child = $this->builder->criterion('oof')->buildChild();

        $this->assertInstanceOf(Child::class, $child);
        $this->assertInstanceOf(StringElement::class, $child->element());
        $this->assertEquals('foo', $child->name());

        $child = $child->setParent(new Form(new ChildrenCollection()));
        $child->submit(['foo' => 'bar']);

        $c = new PrimeCriteria();
        $child->fill($c);

        $this->assertEquals(new PrimeCriteria(['oof' => 'bar']), $c);
    }

    /**
     *
     */
    public function test_criterion_with_attribute_and_transformer_parameter()
    {
        $child = $this->builder->criterion('oof', function ($value) {
            return '/'.strtoupper($value).'/';
        })->buildChild();

        $this->assertInstanceOf(Child::class, $child);
        $this->assertInstanceOf(StringElement::class, $child->element());
        $this->assertEquals('foo', $child->name());

        $child = $child->setParent(new Form(new ChildrenCollection()));
        $child->submit(['foo' => 'bar']);

        $c = new PrimeCriteria();
        $child->fill($c);

        $this->assertEquals(new PrimeCriteria(['oof' => '/BAR/']), $c);
    }

    /**
     *
     */
    public function test_transform()
    {
        $child = $this->builder->transform(function ($value) {
            return '/'.strtoupper($value).'/';
        })->buildChild();

        $this->assertInstanceOf(Child::class, $child);
        $this->assertInstanceOf(StringElement::class, $child->element());
        $this->assertEquals('foo', $child->name());

        $child = $child->setParent(new Form(new ChildrenCollection()));
        $child->submit(['foo' => 'bar']);

        $c = new PrimeCriteria();
        $child->fill($c);

        $this->assertEquals(new PrimeCriteria(['foo' => '/BAR/']), $c);
    }

    /**
     *
     */
    public function test_operator()
    {
        $child = $this->builder->operator('>=')->buildChild();

        $this->assertInstanceOf(Child::class, $child);
        $this->assertInstanceOf(StringElement::class, $child->element());
        $this->assertEquals('foo', $child->name());

        $child = $child->setParent(new Form(new ChildrenCollection()));
        $child->submit(['foo' => 'bar']);

        $c = new PrimeCriteria();
        $child->fill($c);

        $this->assertEquals(new PrimeCriteria(['foo >=' => 'bar']), $c);
    }

    /**
     *
     */
    public function test_in()
    {
        $child = $this->builder->in()->buildChild();

        $this->assertInstanceOf(Child::class, $child);
        $this->assertInstanceOf(StringElement::class, $child->element());
        $this->assertEquals('foo', $child->name());

        $child = $child->setParent(new Form(new ChildrenCollection()));
        $child->submit(['foo' => 'bar']);

        $c = new PrimeCriteria();
        $child->fill($c);

        $this->assertEquals(new PrimeCriteria(['foo :in' => 'bar']), $c);
    }

    /**
     *
     */
    public function test_notIn()
    {
        $child = $this->builder->notIn()->buildChild();

        $this->assertInstanceOf(Child::class, $child);
        $this->assertInstanceOf(StringElement::class, $child->element());
        $this->assertEquals('foo', $child->name());

        $child = $child->setParent(new Form(new ChildrenCollection()));
        $child->submit(['foo' => 'bar']);

        $c = new PrimeCriteria();
        $child->fill($c);

        $this->assertEquals(new PrimeCriteria(['foo :notin' => 'bar']), $c);
    }

    /**
     *
     */
    public function test_between()
    {
        $child = $this->builder->between()->buildChild();

        $this->assertInstanceOf(Child::class, $child);
        $this->assertInstanceOf(StringElement::class, $child->element());
        $this->assertEquals('foo', $child->name());

        $child = $child->setParent(new Form(new ChildrenCollection()));
        $child->submit(['foo' => 'bar']);

        $c = new PrimeCriteria();
        $child->fill($c);

        $this->assertEquals(new PrimeCriteria(['foo :between' => 'bar']), $c);
    }

    /**
     *
     */
    public function test_notEq()
    {
        $child = $this->builder->notEq()->buildChild();

        $this->assertInstanceOf(Child::class, $child);
        $this->assertInstanceOf(StringElement::class, $child->element());
        $this->assertEquals('foo', $child->name());

        $child = $child->setParent(new Form(new ChildrenCollection()));
        $child->submit(['foo' => 'bar']);

        $c = new PrimeCriteria();
        $child->fill($c);

        $this->assertEquals(new PrimeCriteria(['foo !=' => 'bar']), $c);
    }

    /**
     *
     */
    public function test_like()
    {
        $child = $this->builder->like()->buildChild();

        $this->assertInstanceOf(Child::class, $child);
        $this->assertInstanceOf(StringElement::class, $child->element());
        $this->assertEquals('foo', $child->name());

        $child = $child->setParent(new Form(new ChildrenCollection()));
        $child->submit(['foo' => 'bar']);

        $c = new PrimeCriteria();
        $child->fill($c);

        $this->assertEquals(new PrimeCriteria(['foo :like' => 'bar']), $c);
    }

    /**
     *
     */
    public function test_startWith()
    {
        $child = $this->builder->startWith()->buildChild();

        $this->assertInstanceOf(Child::class, $child);
        $this->assertInstanceOf(StringElement::class, $child->element());
        $this->assertEquals('foo', $child->name());

        $child = $child->setParent(new Form(new ChildrenCollection()));
        $child->submit(['foo' => 'bar']);

        $c = new PrimeCriteria();
        $child->fill($c);

        $this->assertEquals(new PrimeCriteria(['foo' => (new Like('bar'))->startsWith()->escape()]), $c);
    }

    /**
     *
     */
    public function test_contains()
    {
        $child = $this->builder->contains()->buildChild();

        $this->assertInstanceOf(Child::class, $child);
        $this->assertInstanceOf(StringElement::class, $child->element());
        $this->assertEquals('foo', $child->name());

        $child = $child->setParent(new Form(new ChildrenCollection()));
        $child->submit(['foo' => 'bar']);

        $c = new PrimeCriteria();
        $child->fill($c);

        $this->assertEquals(new PrimeCriteria(['foo' => (new Like('bar'))->contains()->escape()]), $c);
    }

    /**
     *
     */
    public function test_custom_hydrator()
    {
        $child = $this->builder->hydrator(new Criteria('aaa'))->buildChild();

        $this->assertInstanceOf(Child::class, $child);
        $this->assertInstanceOf(StringElement::class, $child->element());
        $this->assertEquals('foo', $child->name());

        $child = $child->setParent(new Form(new ChildrenCollection()));
        $child->submit(['foo' => 'bar']);

        $c = new PrimeCriteria();
        $child->fill($c);

        $this->assertEquals(new PrimeCriteria(['aaa' => 'bar']), $c);
    }

    /**
     *
     */
    public function test_custom_hydrator_should_have_priority()
    {
        $child = $this->builder->hydrator(new Criteria('aaa'))->criterion()->buildChild();

        $this->assertInstanceOf(Child::class, $child);
        $this->assertInstanceOf(StringElement::class, $child->element());
        $this->assertEquals('foo', $child->name());

        $child = $child->setParent(new Form(new ChildrenCollection()));
        $child->submit(['foo' => 'bar']);

        $c = new PrimeCriteria();
        $child->fill($c);

        $this->assertEquals(new PrimeCriteria(['aaa' => 'bar']), $c);
    }
}

// @todo to delete
interface ChildBuilderInterfaceWithFactory extends ChildBuilderInterface
{
    public function childFactory($factory);
}
