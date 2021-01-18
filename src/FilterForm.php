<?php

namespace Bdf\Form\Filter;

use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Custom\CustomForm;
use Bdf\Prime\Entity\Criteria as PrimeCriteria;

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
     * {@inheritdoc}
     */
    protected function configure(FormBuilderInterface $builder): void
    {
        $builder->generates(PrimeCriteria::class);

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
}
