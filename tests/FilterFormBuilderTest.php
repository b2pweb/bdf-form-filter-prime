<?php

namespace Bdf\Form\Filter;

use Bdf\Form\Aggregate\ArrayElement;
use Bdf\Form\Aggregate\Form;
use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\Value\ValueGeneratorInterface;
use Bdf\Form\Button\ButtonBuilderInterface;
use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\Leaf\BooleanElement;
use Bdf\Form\Leaf\Date\DateTimeElement;
use Bdf\Form\Leaf\FloatElement;
use Bdf\Form\Leaf\IntegerElement;
use Bdf\Form\Leaf\StringElement;
use Bdf\Form\Phone\PhoneElement;
use Bdf\Prime\Entity\Criteria as PrimeCriteria;
use Bdf\Prime\Query\Expression\Like;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FilterFormBuilderTest extends TestCase
{
    /**
     * @var FilterFormBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->builder = new FilterFormBuilder(new FormBuilder());
    }

    /**
     *
     */
    public function test_delegated_methods()
    {
        $builder = new FilterFormBuilder($inner = $this->createMock(FormBuilderInterface::class));

        $inner->expects($this->once())->method('satisfy')->with(function() {}, 'foo', false);
        $this->assertSame($builder, $builder->satisfy(function() {}, 'foo', false));

        $inner->expects($this->once())->method('transformer')->with(function() {}, false);
        $this->assertSame($builder, $builder->transformer(function() {}, false));

        $inner->expects($this->once())->method('value')->with('foo');
        $this->assertSame($builder, $builder->value('foo'));

        $inner->expects($this->once())->method('propertyAccessor')->with($pa = new PropertyAccessor());
        $this->assertSame($builder, $builder->propertyAccessor($pa));

        $inner->expects($this->once())->method('validator')->with($v = $this->createMock(ValidatorInterface::class));
        $this->assertSame($builder, $builder->validator($v));

        $inner->expects($this->once())->method('generator')->with($g = $this->createMock(ValueGeneratorInterface::class));
        $this->assertSame($builder, $builder->generator($g));

        $inner->expects($this->once())->method('generates')->with('Foo');
        $this->assertSame($builder, $builder->generates('Foo'));

        $inner->expects($this->once())->method('csrf')->with('_token')->willReturn($childBuilder = $this->createMock(ChildBuilderInterface::class));
        $this->assertSame($childBuilder, $builder->csrf());

        $inner->expects($this->once())->method('submit')->with('foo')->willReturn($childBuilder = $this->createMock(ButtonBuilderInterface::class));
        $this->assertSame($childBuilder, $builder->submit('foo'));
    }

    /**
     *
     */
    public function test_magic_call()
    {
        $inner = new class extends FormBuilder {
            public function foo() { return 'bar'; }
            public function bar() { return $this; }
            public function baz() { return $this->string('baz'); }
        };

        $builder = new FilterFormBuilder($inner);

        $this->assertSame('bar', $builder->foo());
        $this->assertSame($builder, $builder->bar());
        $this->assertInstanceOf(FilterChildBuilder::class, $builder->baz());
        $form = $builder->buildElement();
        $this->assertTrue(isset($form['baz']));
    }

    /**
     *
     */
    public function test_add()
    {
        $childBuilder = $this->builder->add('foo', StringElement::class);
        $this->assertInstanceOf(FilterChildBuilder::class, $childBuilder);

        $form = $this->builder->buildElement();

        $this->assertInstanceOf(StringElement::class, $form['foo']->element());
    }

    /**
     *
     */
    public function test_string()
    {
        $childBuilder = $this->builder->string('foo');
        $this->assertInstanceOf(FilterChildBuilder::class, $childBuilder);

        $form = $this->builder->buildElement();

        $this->assertInstanceOf(StringElement::class, $form['foo']->element());
    }

    /**
     *
     */
    public function test_integer()
    {
        $childBuilder = $this->builder->integer('foo');
        $this->assertInstanceOf(FilterChildBuilder::class, $childBuilder);

        $form = $this->builder->buildElement();

        $this->assertInstanceOf(IntegerElement::class, $form['foo']->element());
    }

    /**
     *
     */
    public function test_float()
    {
        $childBuilder = $this->builder->float('foo');
        $this->assertInstanceOf(FilterChildBuilder::class, $childBuilder);

        $form = $this->builder->buildElement();

        $this->assertInstanceOf(FloatElement::class, $form['foo']->element());
    }

    /**
     *
     */
    public function test_boolean()
    {
        $childBuilder = $this->builder->boolean('foo');
        $this->assertInstanceOf(FilterChildBuilder::class, $childBuilder);

        $form = $this->builder->buildElement();

        $this->assertInstanceOf(BooleanElement::class, $form['foo']->element());
    }

    /**
     *
     */
    public function test_phone()
    {
        $childBuilder = $this->builder->phone('foo');
        $this->assertInstanceOf(FilterChildBuilder::class, $childBuilder);

        $form = $this->builder->buildElement();

        $this->assertInstanceOf(PhoneElement::class, $form['foo']->element());
    }

    /**
     *
     */
    public function test_dateTime()
    {
        $childBuilder = $this->builder->dateTime('foo');
        $this->assertInstanceOf(FilterChildBuilder::class, $childBuilder);

        $form = $this->builder->buildElement();

        $this->assertInstanceOf(DateTimeElement::class, $form['foo']->element());
    }

    /**
     *
     */
    public function test_embedded()
    {
        $childBuilder = $this->builder->embedded('foo', function ($builder) {
            $builder->string('bar');
        });
        $this->assertInstanceOf(FilterChildBuilder::class, $childBuilder);

        $form = $this->builder->buildElement();

        $this->assertInstanceOf(Form::class, $form['foo']->element());
        $this->assertInstanceOf(StringElement::class, $form['foo']->element()['bar']->element());
    }

    /**
     *
     */
    public function test_array()
    {
        $childBuilder = $this->builder->array('foo');
        $this->assertInstanceOf(FilterChildBuilder::class, $childBuilder);

        $form = $this->builder->buildElement();

        $this->assertInstanceOf(ArrayElement::class, $form['foo']->element());
    }

    /**
     *
     */
    public function test_functional()
    {
        $this->builder->string('foo')->criterion('_foo');
        $this->builder->string('bar')->notEq();
        $this->builder->string('baz')->contains();
        $this->builder->generates(PrimeCriteria::class);

        $form = $this->builder->buildElement();
        $form->submit([
            'foo' => 'aaa',
            'bar' => 'bbb',
            'baz' => 'ccc',
        ]);
        $this->assertEquals(new PrimeCriteria([
            '_foo' => 'aaa',
            'bar !=' => 'bbb',
            'baz' => (new Like('ccc'))->contains()->escape(),
        ]), $form->value());
    }

    /**
     *
     */
    public function test_search()
    {
        $this->builder->search('foo');
        $form = $this->builder->buildElement();

        $this->assertInstanceOf(StringElement::class, $form['foo']->element());

        $form->attach(new PrimeCriteria())->submit(['foo' => 'bar']);
        $this->assertEquals(new PrimeCriteria(['foo :like' => 'bar']), $form->value());
    }

    /**
     *
     */
    public function test_searchBegins()
    {
        $this->builder->searchBegins('foo');
        $form = $this->builder->buildElement();

        $this->assertInstanceOf(StringElement::class, $form['foo']->element());

        $form->attach(new PrimeCriteria())->submit(['foo' => 'bar']);
        $this->assertEquals(new PrimeCriteria(['foo' => (new Like('bar'))->startsWith()->escape()]), $form->value());
    }

    /**
     *
     */
    public function test_searchContains()
    {
        $this->builder->searchContains('foo');
        $form = $this->builder->buildElement();

        $this->assertInstanceOf(StringElement::class, $form['foo']->element());

        $form->attach(new PrimeCriteria())->submit(['foo' => 'bar']);
        $this->assertEquals(new PrimeCriteria(['foo' => (new Like('bar'))->contains()->escape()]), $form->value());
    }
}
