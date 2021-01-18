<?php

namespace Bdf\Form\Filter;

use Bdf\Prime\Entity\Criteria as PrimeCriteria;
use Bdf\Prime\Query\Expression\Like;
use PHPUnit\Framework\TestCase;

class FilterFormTest extends TestCase
{
    /**
     *
     */
    public function test()
    {
        $form = new PersonFormFilter();

        $form->submit([
            'firstName' => 'J',
            'lastName' => 'Smi',
            'age' => [20, 55],
        ]);

        $this->assertTrue($form->valid());
        $this->assertEquals(new PrimeCriteria([
            'firstName' => (new Like('J'))->startsWith()->escape(),
            'lastName' => (new Like('Smi'))->startsWith()->escape(),
            'age :between' => [20, 55],
        ]), $form->value());
    }
}

class PersonFormFilter extends FilterForm
{
    protected function configureFilters(FilterFormBuilder $builder): void
    {
        $builder->string('firstName')->startWith();
        $builder->searchBegins('lastName');
        $builder->embedded('age', function ($builder) {
            $builder->integer('0')->setter();
            $builder->integer('1')->setter();
        })->between();
    }
}
