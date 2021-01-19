<?php

namespace Bdf\Form\Filter;

use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Prime\Connection\ConnectionRegistry;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\Entity\Criteria as PrimeCriteria;
use Bdf\Prime\Locatorizable;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Query\Expression\Like;
use Bdf\Prime\Query\Query;
use Bdf\Prime\Repository\RepositoryInterface;
use Bdf\Prime\ServiceLocator;
use PHPUnit\Framework\TestCase;

class FilterFormTest extends TestCase
{
    /**
     *
     */
    protected function tearDown(): void
    {
        if (Locatorizable::locator()) {
            Locatorizable::locator()->clearRepositories();
            Locatorizable::configure(null);
        }
    }

    /**
     *
     */
    public function test_simple()
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

    /**
     *
     */
    public function test_should_use_entity_criteria_if_defined()
    {
        $prime = new ServiceLocator(new ConnectionManager(new ConnectionRegistry(['test' => 'sqlite::memory:'])));
        $prime->registerRepository(Person::class, $repository = $this->createMock(RepositoryInterface::class));
        $form = new PersonFormFilter(null, $prime);

        // Change when value cache is fixed
        $repository->expects($this->exactly(2))->method('criteria')->willReturnCallback(function () { return new PrimeCriteria(); });

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

    /**
     *
     */
    public function test_with_apply()
    {
        $prime = new ServiceLocator(new ConnectionManager(new ConnectionRegistry(['test' => 'sqlite::memory:'])));
        $form = new PersonFormFilter(null, $prime);

        $form->submit([
            'firstName' => 'J',
            'lastName' => 'Smi',
            'age' => [20, 55],
        ]);

        $query = $prime->repository(Person::class)->builder();
        $this->assertSame($query, $form->apply($query));

        $this->assertEquals('SELECT t0.* FROM person t0 WHERE t0.firstName LIKE \'J%\' AND t0.lastName LIKE \'Smi%\' AND t0.age BETWEEN 20 AND 55', $query->toRawSql());
    }

    /**
     *
     */
    public function test_with_apply_should_use_locatorizable_if_prime_is_not_provided_in_constructor()
    {
        Locatorizable::configure($prime = new ServiceLocator(new ConnectionManager(new ConnectionRegistry(['test' => 'sqlite::memory:']))));

        $form = new PersonFormFilter();

        $form->submit([
            'firstName' => 'J',
            'lastName' => 'Smi',
            'age' => [20, 55],
        ]);

        $query = $prime->repository(Person::class)->builder();
        $this->assertSame($query, $form->apply($query));

        $this->assertEquals('SELECT t0.* FROM person t0 WHERE t0.firstName LIKE \'J%\' AND t0.lastName LIKE \'Smi%\' AND t0.age BETWEEN 20 AND 55', $query->toRawSql());
    }

    /**
     *
     */
    public function test_with_apply_form_not_submitted()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('The form is not valid');

        $prime = new ServiceLocator(new ConnectionManager(new ConnectionRegistry(['test' => 'sqlite::memory:'])));
        $form = new PersonFormFilter(null, $prime);

        $query = $prime->repository(Person::class)->builder();
        $form->apply($query);
    }

    /**
     *
     */
    public function test_with_query()
    {
        $prime = new ServiceLocator(new ConnectionManager(new ConnectionRegistry(['test' => 'sqlite::memory:'])));
        $form = new PersonFormFilter(null, $prime);

        $form->submit([
            'firstName' => 'J',
            'lastName' => 'Smi',
            'age' => [20, 55],
        ]);

        $query = $form->query();

        $this->assertInstanceOf(Query::class, $query);
        $this->assertEquals('SELECT t0.* FROM person t0 WHERE t0.firstName LIKE \'J%\' AND t0.lastName LIKE \'Smi%\' AND t0.age BETWEEN 20 AND 55', $query->toRawSql());
    }

    /**
     *
     */
    public function test_query_without_service_locator()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Prime should be provided on the constructor');

        $form = new PersonFormFilter();

        $form->submit([
            'firstName' => 'J',
            'lastName' => 'Smi',
            'age' => [20, 55],
        ]);

        $form->query();
    }

    /**
     *
     */
    public function test_query_without_entity()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('The entity class is not defined');

        $form = new class extends FilterForm {
            protected function configureFilters(FilterFormBuilder $builder): void
            {
            }
        };

        $form->submit([]);

        $form->query();
    }

    /**
     *
     */
    public function test_query_with_invalid_entity()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('The entity stdClass is not valid');

        $prime = new ServiceLocator(new ConnectionManager(new ConnectionRegistry(['test' => 'sqlite::memory:'])));
        $form = new class(null, $prime) extends FilterForm {
            protected function configureFilters(FilterFormBuilder $builder): void
            {
                $this->setEntity(\stdClass::class);
            }
        };

        $form->submit([]);

        $form->query();
    }

    /**
     *
     */
    public function test_provide_FilterFormBuilder_on_constructor_should_not_be_decorated()
    {
        $builder = new FilterFormBuilder(new FormBuilder());
        $form = new class($builder) extends FilterForm {
            public $builder;

            protected function configureFilters(FilterFormBuilder $builder): void
            {
                $this->builder = $builder;
            }
        };

        $form->submit([]);
        $this->assertSame($builder, $form->builder);
    }
}

class PersonFormFilter extends FilterForm
{
    protected function configureFilters(FilterFormBuilder $builder): void
    {
        $this->setEntity(Person::class);

        $builder->string('firstName')->startWith();
        $builder->searchBegins('lastName');
        $builder->embedded('age', function ($builder) {
            $builder->integer('0')->setter();
            $builder->integer('1')->setter();
        })->between();
    }
}

class Person
{
    public $firstName;
    public $lastName;
    public $age;
}

class PersonMapper extends Mapper
{
    public function schema()
    {
        return [
            'connection' => 'test',
            'table' => 'person',
        ];
    }

    public function buildFields($builder)
    {
        $builder->string('firstName')->primary();
        $builder->string('lastName')->primary();
        $builder->integer('age');
    }
}
