<?php

namespace Bdf\Form\Filter;

use BadMethodCallException;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Custom\CustomForm;
use Bdf\Prime\Entity\Criteria as PrimeCriteria;
use Bdf\Prime\Query\Contract\Whereable;

/**
 * Base filter form without handling collection or repository instance
 * Prefer use dedicated implementations
 *
 * @see FilterForm For use base with prime repository system
 * @method PrimeCriteria value()
 */
abstract class BaseFilterForm extends CustomForm
{
    /**
     * {@inheritdoc}
     */
    protected function configure(FormBuilderInterface $builder): void
    {
        /** @psalm-suppress RedundantCondition */
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
}
