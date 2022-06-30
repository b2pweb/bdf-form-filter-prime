<?php

namespace Bdf\Form\Filter;

use Bdf\Form\Aggregate\FormBuilder;
use Bdf\Prime\Connection\ConnectionRegistry;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\Entity\Criteria as PrimeCriteria;
use Bdf\Prime\MongoDB\Collection\MongoCollectionLocator;
use Bdf\Prime\MongoDB\Document\DocumentMapper;
use Bdf\Prime\MongoDB\Mongo;
use Bdf\Prime\MongoDB\Query\Compiled\ReadQuery;
use Bdf\Prime\MongoDB\Query\MongoQuery;
use Bdf\Prime\Query\Expression\Like;
use Bdf\Prime\ServiceLocator;
use PHPUnit\Framework\TestCase;

class MongoFilterFormTest extends TestCase
{
    /**
     *
     */
    protected function setUp(): void
    {
        if (!class_exists(MongoCollectionLocator::class)) {
            $this->markTestSkipped('Package "b2pweb/bdf-prime-mongodb" v2 is required');
        }
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        if (Mongo::isConfigured()) {
            Mongo::configure(null);
        }
    }

    /**
     *
     */
    public function test_simple()
    {
        $form = new PersonDocumentFormFilter();

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
        $prime = new ServiceLocator(new ConnectionManager(new ConnectionRegistry(['mongo' => 'mongodb://localhost'])));
        $locator = new MongoCollectionLocator($prime->connections());
        $form = new PersonDocumentFormFilter(null, $locator);

        $form->submit([
            'firstName' => 'J',
            'lastName' => 'Smi',
            'age' => [20, 55],
        ]);

        $query = $locator->collection(PersonDocument::class)->query();
        $this->assertSame($query, $form->apply($query));

        $this->assertEquals(new ReadQuery('person', [
            'firstName' => ['$regex' => '^J.*$', '$options' => 'i'],
            'lastName' => ['$regex' => '^Smi.*$', '$options' => 'i'],
            '$and' => [
                ['age' => ['$gte' => 20]],
                ['age' => ['$lte' => 55]],
            ],
        ]), $query->compile());
    }

    /**
     *
     */
    public function test_with_apply_should_use_static_locator_if_prime_is_not_provided_in_constructor()
    {
        $prime = new ServiceLocator(new ConnectionManager(new ConnectionRegistry(['mongo' => 'mongodb://localhost'])));
        $locator = new MongoCollectionLocator($prime->connections());
        Mongo::configure($locator);

        $form = new PersonDocumentFormFilter();

        $form->submit([
            'firstName' => 'J',
            'lastName' => 'Smi',
            'age' => [20, 55],
        ]);

        $query = $locator->collection(PersonDocument::class)->query();
        $this->assertSame($query, $form->apply($query));

        $this->assertEquals(new ReadQuery('person', [
            'firstName' => ['$regex' => '^J.*$', '$options' => 'i'],
            'lastName' => ['$regex' => '^Smi.*$', '$options' => 'i'],
            '$and' => [
                ['age' => ['$gte' => 20]],
                ['age' => ['$lte' => 55]],
            ],
        ]), $query->compile());
    }

    /**
     *
     */
    public function test_with_apply_form_not_submitted()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('The form is not valid');

        $prime = new ServiceLocator(new ConnectionManager(new ConnectionRegistry(['mongo' => 'mongodb://localhost'])));
        $locator = new MongoCollectionLocator($prime->connections());
        $form = new PersonDocumentFormFilter(null, $locator);

        $query = $locator->collection(PersonDocument::class)->query();
        $form->apply($query);
    }

    /**
     *
     */
    public function test_with_query()
    {
        $prime = new ServiceLocator(new ConnectionManager(new ConnectionRegistry(['mongo' => 'mongodb://localhost'])));
        $locator = new MongoCollectionLocator($prime->connections());
        $form = new PersonDocumentFormFilter(null, $locator);

        $form->submit([
            'firstName' => 'J',
            'lastName' => 'Smi',
            'age' => [20, 55],
        ]);

        $query = $form->query();

        $this->assertInstanceOf(MongoQuery::class, $query);

        $this->assertEquals(new ReadQuery('person', [
            'firstName' => ['$regex' => '^J.*$', '$options' => 'i'],
            'lastName' => ['$regex' => '^Smi.*$', '$options' => 'i'],
            '$and' => [
                ['age' => ['$gte' => 20]],
                ['age' => ['$lte' => 55]],
            ],
        ]), $query->compile());
    }

    /**
     *
     */
    public function test_query_without_service_locator()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('MongoCollectionLocator should be provided on the constructor');

        $form = new PersonDocumentFormFilter();

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
        $this->expectExceptionMessage('The document class is not defined');

        $form = new class extends MongoFilterForm {
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
    public function test_provide_FilterFormBuilder_on_constructor_should_not_be_decorated()
    {
        $builder = new FilterFormBuilder(new FormBuilder());
        $form = new class($builder) extends MongoFilterForm {
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

if (PHP_VERSION_ID > 70400) {
    class PersonDocumentFormFilter extends MongoFilterForm
    {
        protected function configureFilters(FilterFormBuilder $builder): void
        {
            $this->setDocument(PersonDocument::class);

            $builder->string('firstName')->startWith();
            $builder->searchBegins('lastName');
            $builder->embedded('age', function ($builder) {
                $builder->integer('0')->setter();
                $builder->integer('1')->setter();
            })->between();
        }
    }

    class PersonDocument
    {
        public ?string $firstName = null;
        public ?string $lastName = null;
        public ?int $age = null;
    }

    class PersonDocumentMapper extends DocumentMapper
    {
        public function connection(): string
        {
            return 'mongo';
        }

        public function collection(): string
        {
            return 'person';
        }
    }
}
