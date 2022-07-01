<?php

namespace Bdf\Form\Filter;

use BadMethodCallException;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Custom\CustomForm;
use Bdf\Prime\Entity\Criteria as PrimeCriteria;
use Bdf\Prime\MongoDB\Collection\MongoCollectionInterface;
use Bdf\Prime\MongoDB\Collection\MongoCollectionLocator;
use Bdf\Prime\MongoDB\Mongo;
use Bdf\Prime\MongoDB\Query\MongoQuery;

/**
 * Base type for declare a filter form for a mongo collection
 * Works like @see CustomForm but for build filters
 *
 * Note: package "b2pweb/bdf-prime-mongodb" as version 2 is required
 *
 * <code>
 * // Declaration
 * class MyFilters extends MongoFilterForm
 * {
 *     public function configureFilters(FilterFormBuilder $builder): void
 *     {
 *         // Build filter fields
 *         // Will add a "foo LIKE xxx%"
 *         $builder->searchBegins('foo');
 *
 *         // Will add a "age BETWEEN ? AND ?"
 *         $builder->embedded('age', function ($builder) {
 *             $builder->integer('0')->setter();
 *             $builder->integer('1')->setter();
 *         })->between();
 *     }
 * }
 *
 * // Usage
 * $form = new MyFilters(); // Directly instantiate the form
 * $form = $this->registry->elementBuilder(MyFilters::class)->buildElement(); // Use registry and builder
 *
 * // Submit form
 * // Note: if some constraints has been added, call `$form->valid()` and `$form->error()` to check errors
 * $form->submit($request->query->all());
 *
 * // Get generated criteria
 * $criteria = $form->value();
 *
 * // Call prime with criteria
 * $list = MyDocument::where($criteria->all())->paginate();
 *
 * return $this->render('list', ['entities' => $list]);
 * </code>
 *
 * @method PrimeCriteria value()
 */
abstract class MongoFilterForm extends BaseFilterForm
{
    /**
     * @var MongoCollectionLocator|null
     */
    private $locator;

    /**
     * @var class-string|null
     */
    private $document;

    /**
     * FilterForm constructor.
     *
     * @param FormBuilderInterface|null $builder
     * @param MongoCollectionLocator|null $locator
     */
    public function __construct(?FormBuilderInterface $builder = null, ?MongoCollectionLocator $locator = null)
    {
        parent::__construct($builder);

        $this->locator = $locator;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(FormBuilderInterface $builder): void
    {
        $builder->generates(PrimeCriteria::class);

        parent::configure($builder);
    }

    /**
     * Build and configure a query according to filters
     * Note: The entity class name must be provided
     *
     * <code>
     * class MyFilters extends FilterForm
     * {
     *     public function configureFilters(FilterFormBuilder $builder): void
     *     {
     *         // Configure the entity
     *         $this->setEntity(MyEntity::class);
     *         // Configure filters...
     *     }
     * }
     *
     * // Instantiate the form using container to ensure that the prime service locator is injected
     * $form = $this->container->get(MyFilters::class);
     *
     * // Submit filters and get the query
     * $entities = $form->submit($request->query->all())->query()->all();
     * </code>
     *
     * @return MongoQuery
     *
     * @throws BadMethodCallException When the form is not configured to create the query
     *
     * @see FilterForm::setEntity() To define the entity class
     */
    final public function query(): MongoQuery
    {
        return $this->apply($this->collection()->query());
    }

    /**
     * Define the handled document class
     * This is used by the `query()` method to create the query
     *
     * @param class-string $document
     */
    protected final function setDocument(string $document): void
    {
        $this->document = $document;
    }

    /**
     * Get the query related to the entity
     *
     * @return MongoCollectionInterface
     */
    private function collection(): MongoCollectionInterface
    {
        if (!$this->document)  {
            throw new BadMethodCallException('The document class is not defined');
        }

        if (!$this->locator) {
            if (!Mongo::isConfigured()) {
                throw new BadMethodCallException('MongoCollectionLocator should be provided on the constructor');
            }

            $this->locator = Mongo::locator();
        }

        return $this->locator->collection($this->document);
    }
}
