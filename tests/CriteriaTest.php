<?php

namespace Bdf\Form\Filter;

use Bdf\Form\Aggregate\Collection\ChildrenCollection;
use Bdf\Form\Aggregate\Form;
use Bdf\Form\Child\ChildBuilder;
use Bdf\Form\Child\ChildInterface;
use Bdf\Form\Leaf\AnyElementBuilder;
use Bdf\Form\Leaf\StringElementBuilder;
use Bdf\Form\PropertyAccess\HydratorInterface;
use Bdf\Prime\Entity\Criteria as PrimeCriteria;
use PHPUnit\Framework\TestCase;

class CriteriaTest extends TestCase
{
    /**
     * @dataProvider provideValues
     */
    public function test_hydrate($value)
    {
        $child = (new ChildBuilder('foo', new AnyElementBuilder()))->hydrator(new Criteria())->buildChild();
        $child->element()->import($value);
        $child = $child->setParent($parent = new Form(new ChildrenCollection()));

        $c = new PrimeCriteria();
        $child->fill($c);

        $this->assertEquals(new PrimeCriteria(['foo' => $value]), $c);
    }

    public function provideValues()
    {
        return [
            ['bar'],
            ['0'],
            [1],
            [0],
            [0.0],
            [false],
        ];
    }

    /**
     *
     */
    public function test_hydrate_no_value_on_field_should_ignore_criterion()
    {
        $child = (new ChildBuilder('foo', new StringElementBuilder()))->hydrator(new Criteria())->buildChild();
        $child = $child->setParent($parent = new Form(new ChildrenCollection()));

        $c = new PrimeCriteria();
        $child->fill($c);

        $this->assertEquals(new PrimeCriteria([]), $c);
    }

    /**
     *
     */
    public function test_hydrate_with_custom_property_name()
    {
        $child = (new ChildBuilder('foo', new StringElementBuilder()))->hydrator(new Criteria('aaa'))->buildChild();
        $child->element()->import('bar');
        $child = $child->setParent($parent = new Form(new ChildrenCollection()));

        $c = new PrimeCriteria();
        $child->fill($c);

        $this->assertEquals(new PrimeCriteria(['aaa' => 'bar']), $c);
    }

    /**
     *
     */
    public function test_hydrate_with_transformer()
    {
        $child = (new ChildBuilder('foo', new StringElementBuilder()))->hydrator(new Criteria(function ($value, $input, $mode, $hydrator) {
            $this->assertInstanceOf(ChildInterface::class, $input);
            $this->assertSame(HydratorInterface::HYDRATION, $mode);
            $this->assertInstanceOf(Criteria::class, $hydrator);

            return strtoupper($value);
        }))->buildChild();
        $child->element()->import('bar');
        $child = $child->setParent($parent = new Form(new ChildrenCollection()));

        $c = new PrimeCriteria();
        $child->fill($c);

        $this->assertEquals(new PrimeCriteria(['foo' => 'BAR']), $c);
    }

    /**
     *
     */
    public function test_hydrate_with_custom_accessor()
    {
        $child = (new ChildBuilder('foo', new StringElementBuilder()))->hydrator(new Criteria(null, null, function (PrimeCriteria $criteria, $value, $mode, $hydrator) {
            $this->assertEquals('bar', $value);
            $this->assertSame(HydratorInterface::HYDRATION, $mode);
            $this->assertInstanceOf(Criteria::class, $hydrator);

            $criteria->add('aaa', 'bbb');
        }))->buildChild();
        $child->element()->import('bar');
        $child = $child->setParent($parent = new Form(new ChildrenCollection()));

        $c = new PrimeCriteria();
        $child->fill($c);

        $this->assertEquals(new PrimeCriteria(['aaa' => 'bbb']), $c);
    }

    /**
     *
     */
    public function test_hydrate_not_a_criteria_instance()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('$criteria must be an instance of '.PrimeCriteria::class);

        $child = (new ChildBuilder('foo', new StringElementBuilder()))->hydrator(new Criteria())->buildChild();
        $child->element()->import('foo');
        $child = $child->setParent($parent = new Form(new ChildrenCollection()));

        $c = new \stdClass();
        $child->fill($c);
    }
}
