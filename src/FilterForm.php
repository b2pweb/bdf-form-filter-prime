<?php

namespace Bdf\Form\Filter;

use BadMethodCallException;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Custom\CustomForm;
use Bdf\Prime\Entity\Criteria as PrimeCriteria;
use Bdf\Prime\Locatorizable;
use Bdf\Prime\Query\Contract\Whereable;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Repository\RepositoryInterface;
use Bdf\Prime\ServiceLocator;

/**
 * Base type for declare a filter form
 * Works like @see CustomForm but for build filters
 *
 * <code>
 * // Declaration
 * class MyFilters extends FilterForm
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
 * $list = MyEntity::where($criteria->all())->paginate();
 *
 * return $this->render('list', ['entities' => $list]);
 * </code>
 *
 * @method PrimeCriteria value()
 */
abstract class FilterForm extends CustomForm
{
    /**
     * @var ServiceLocator|null
     */
    private $prime;

    /**
     * @var class-string|null
     */
    private $entity;

    /**
     * FilterForm constructor.
     *
     * @param FormBuilderInterface|null $builder
     * @param ServiceLocator|null $prime
     */
    public function __construct(?FormBuilderInterface $builder = null, ?ServiceLocator $prime = null)
    {
        parent::__construct($builder);

        $this->prime = $prime;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(FormBuilderInterface $builder): void
    {
        $builder->generates(function (): PrimeCriteria {
            if ($this->entity && ($this->prime || Locatorizable::isActiveRecordEnabled())) {
                return $this->repository()->criteria();
            }

            return new PrimeCriteria();
        });

        if (!$builder instanceof FilterFormBuilder) {
            $builder = new FilterFormBuilder($builder);
        }

        $this->configureFilters($builder);
    }

    /**
     * Configure the filters by using the filter builder
     *
     * @param FilterFormBuilder $builder
     */
    abstract protected function configureFilters(FilterFormBuilder $builder): void;

    /**
     * Apply the criteria to the query
     *
     * <code>
     * $query = MyEntity::builder();
     * $entities = $form->submit($request->query()->all())->apply($query)->all();
     * </code>
     *
     * @param Q $query Query to filter
     *
     * @return Q
     * @template Q as Whereable
     *
     * @throws BadMethodCallException If the form is invalid
     */
    final public function apply(Whereable $query): Whereable
    {
        if (!$this->valid()) {
            throw new BadMethodCallException('The form is not valid');
        }

        return $query->where($this->value()->all());
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
     * @return QueryInterface
     *
     * @throws BadMethodCallException When the form is not configured to create the query
     *
     * @see FilterForm::setEntity() To define the entity class
     */
    final public function query(): QueryInterface
    {
        return $this->apply($this->repository()->queries()->builder());
    }

    /**
     * Define the handled entity
     * This is used by the `query()` method to create the query
     *
     * @param class-string $entity
     */
    protected final function setEntity(string $entity): void
    {
        $this->entity = $entity;
    }

    /**
     * Get the query related to the entity
     *
     * @return RepositoryInterface
     */
    private function repository(): RepositoryInterface
    {
        if (!$this->entity)  {
            throw new BadMethodCallException('The entity class is not defined');
        }

        if (!$this->prime) {
            if (!Locatorizable::isActiveRecordEnabled()) {
                throw new BadMethodCallException('Prime should be provided on the constructor');
            }

            $this->prime = Locatorizable::locator();
        }

        if (($repository = $this->prime->repository($this->entity)) === null) {
            throw new BadMethodCallException('The entity '.$this->entity.' is not valid');
        }

        return $repository;
    }
}
