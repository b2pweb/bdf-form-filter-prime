<?php

namespace Bdf\Form\Filter;

use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\Child\ChildInterface;
use Bdf\Form\Child\ChildParameters;
use Bdf\Form\PropertyAccess\ExtractorInterface;
use Bdf\Form\PropertyAccess\HydratorInterface;
use Bdf\Form\Util\HttpValue;
use Bdf\Prime\Query\Expression\Like;

/**
 * Decorate a ChildBuilderInterface to handle filter building
 *
 * <code>
 * $builder->string('foo')->startWith(); // "foo LIKE xxx%"
 * $builder->array('ids')->integer()->in(); // "foo IN (14, 58, 74)"
 * $builder->string('foo')->operator(':regex')->transformer(function ($value) { return '^.'.$value.'.$'; }); // Perform a regex search
 * </code>
 *
 * @template B as \Bdf\Form\ElementBuilderInterface
 * @implements ChildBuilderInterface<B>
 *
 * @mixin B
 */
class FilterChildBuilder implements ChildBuilderInterface
{
    /**
     * The inner builder
     *
     * @var ChildBuilderInterface<B>
     */
    private $builder;

    /**
     * @var bool
     */
    private $isCriterion = false;

    /**
     * @var string|null
     */
    private $attribute;

    /**
     * @var string|null
     */
    private $operator;

    /**
     * @var callable|null
     */
    private $criteriaTransformer;


    /**
     * FilterChildBuilder constructor.
     *
     * @param ChildBuilderInterface<B> $builder
     */
    public function __construct(ChildBuilderInterface $builder)
    {
        $this->builder = $builder;

        $builder->addParametersConfigurator([$this, 'provideDefaultHydrator']);
    }

    /**
     * {@inheritdoc}
     */
    public function hydrator(HydratorInterface $hydrator)
    {
        $this->builder->hydrator($hydrator);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function extractor(ExtractorInterface $extractor)
    {
        $this->builder->extractor($extractor);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function filter($filter, bool $append = true)
    {
        $this->builder->filter($filter, $append);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function default($default)
    {
        $this->builder->default($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function depends(string ...$inputNames)
    {
        $this->builder->depends(...$inputNames);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function modelTransformer($transformer, bool $append = true)
    {
        $this->builder->modelTransformer($transformer, $append);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addParametersConfigurator(callable $configurator)
    {
        $this->builder->addParametersConfigurator($configurator);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function buildChild(): ChildInterface
    {
        return $this->builder->buildChild();
    }

    /**
     * Specify the aimed attribute
     *
     * <code>
     * // Mark the current field as criterion
     * $builder->criterion();
     *
     * // Use "foo" as criterion attribute, instead of the field name
     * $builder->criterion('foo');
     *
     * // Define a transformer to normalize the criterion value
     * // First parameter is the field value
     * // Second is the field object
     * $builder->criterion('foo', function ($value, ChildInterface $child) {
     *     return '/'.$value.'/';
     * });
     * </code>
     *
     * @param null|string $name The attribute name. If not provided, the field name will be used
     * @param null|callable $criteriaTransformer Transform the criteria value
     *
     * @return $this
     */
    public function criterion(?string $name = null, ?callable $criteriaTransformer = null)
    {
        $this->isCriterion = true;

        if ($name !== null) {
            $this->attribute = $name;
        }

        if ($criteriaTransformer !== null) {
            $this->criteriaTransformer = $criteriaTransformer;
        }

        return $this;
    }

    /**
     * Specify the filter transformer
     *
     * <code>
     * // First parameter is the field value
     * // Second is the field object
     * $builder->transform(function ($value, ChildInterface $child) {
     *     return '/'.$value.'/';
     * });
     * </code>
     *
     * @param callable $criteriaTransformer The transformer callback. Takes as first argument the value, and second the input
     *
     * @return $this
     */
    public function transform(callable $criteriaTransformer)
    {
        return $this->criterion(null, $criteriaTransformer);
    }

    /**
     * Define the comparison operator
     *
     * @param string $operator The criterion operator (ex: ">=", ":regex", ...)
     *
     * @return $this
     */
    public function operator(string $operator)
    {
        $this->criterion();

        $this->operator = ' '.$operator;

        return $this;
    }

    /**
     * Use the 'in' operator
     * The form field should be an array
     *
     * <code>
     * $builder->array('ids')->integer()->in();
     * </code>
     *
     * @return $this
     */
    public function in()
    {
        return $this->operator(':in');
    }

    /**
     * Use the 'not in' operator
     * The form field should be an array
     *
     * <code>
     * $builder->array('deniedIds')->integer()->notIn();
     * </code>
     *
     * @return $this
     */
    public function notIn()
    {
        return $this->operator(':notin');
    }

    /**
     * Create a 'between' operator
     * The form field should be an array with exactly 2 elements
     *
     * <code>
     * $form->embedded('dates', function ($builder) {
     *     $builder->dateTime('0')->setter(); // The beginning of the interval
     *     $builder->dateTime('1')->setter(); // The end of the interval
     * })->between();
     * </code>
     *
     * @return $this
     */
    public function between()
    {
        return $this->operator(':between');
    }

    /**
     * Create a 'not equal' operator
     *
     * @return $this
     */
    public function notEq()
    {
        return $this->operator('!=');
    }

    /**
     * Create a 'like' operator
     * Calling this method is not necessary when use `startWith()` or `contains()`
     *
     * Note: Use this method will not escape LIKE metacharacters
     *
     * @return $this
     *
     * @see FilterChildBuilder::startWith() To define a "LIKE xxx%"
     * @see FilterChildBuilder::contains() To define a "LIKE %xxx%"
     */
    public function like()
    {
        return $this->operator(':like');
    }

    /**
     * Define a like criterion for perform a "start with" search
     * The created criterion will be in form : "LIKE xxx%"
     *
     * @param bool $escape True to escape the value (by default)
     *
     * @return $this
     *
     * @see FilterChildBuilder::like() To define a simple like query
     * @see FilterChildBuilder::contains() To define a "LIKE %xxx%"
     */
    public function startWith(bool $escape = true)
    {
        return $this->criterion(null, function (?string $value) use($escape) {
            if (HttpValue::isEmpty($value)) {
                return null;
            }

            return (new Like($value))->escape($escape)->startsWith();
        });
    }

    /**
     * Define a like criterion for perform a "contains" search
     * The created criterion will be in form : "LIKE %xxx%"
     *
     * @param bool $escape True to escape the value (by default)
     *
     * @return $this
     *
     * @see FilterChildBuilder::like() To define a simple like query
     * @see FilterChildBuilder::startWith() To define a "LIKE xxx%"
     */
    public function contains(bool $escape = true)
    {
        return $this->criterion(null, function (?string $value) use($escape) {
            if (HttpValue::isEmpty($value)) {
                return null;
            }

            return (new Like($value))->escape($escape)->contains();
        });
    }

    /**
     * Forward call to the inner builder
     *
     * @param string $method
     * @param array $arguments
     *
     * @return $this|mixed
     */
    public function __call(string $method, array $arguments)
    {
        $ret = $this->builder->$method(...$arguments);

        return $ret === $this->builder ? $this : $ret;
    }

    /**
     * Provide criteria hydrator if not yet defined on the child
     *
     * @param ChildParameters $parameters
     * @internal
     */
    public function provideDefaultHydrator(ChildParameters $parameters): void
    {
        if (!$parameters->hydrator) {
            $parameters->hydrator = $this->createHydrator($parameters->name);
        }
    }

    /**
     * Get the criteria hydrator
     *
     * @param string $childName The child name, used as default attribute name
     *
     * @return Criteria|null
     */
    private function createHydrator(string $childName): ?Criteria
    {
        if (!$this->isCriterion) {
            return null;
        }

        $attribute = $this->attribute ?: $childName;

        if ($this->operator) {
            $attribute .= $this->operator;
        }

        if ($this->criteriaTransformer !== null) {
            return new Criteria($attribute, $this->criteriaTransformer);
        }

        return new Criteria($attribute);
    }
}
