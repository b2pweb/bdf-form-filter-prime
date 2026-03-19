<?php


namespace Bdf\Form\Filter;

use Bdf\Form\Aggregate\ArrayElementBuilder;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\Value\ValueGeneratorInterface;
use Bdf\Form\Button\ButtonBuilderInterface;
use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Leaf\AnyElementBuilder;
use Bdf\Form\Leaf\BooleanElementBuilder;
use Bdf\Form\Leaf\Date\DateTimeElementBuilder;
use Bdf\Form\Leaf\EnumElementBuilder;
use Bdf\Form\Leaf\FloatElementBuilder;
use Bdf\Form\Leaf\IntegerElementBuilder;
use Bdf\Form\Leaf\StringElementBuilder;
use Bdf\Form\Phone\PhoneElementBuilder;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UnitEnum;

use function is_numeric;
use function max;

/**
 * Wrap a form builder for build prime filters
 *
 * <code>
 * $builder = new FilterFormBuilder(new FormBuilder());
 *
 * $builder->generates(Criteria::class); // The form filters should return an instance of Criteria
 *
 * $builder->searchBegins('firstName');
 * $builder->string('search')->criterion('customFilter');
 *
 * $form = $builder->buildElement();
 * </code>
 */
class FilterFormBuilder implements FormBuilderInterface
{
    /**
     * @var FormBuilderInterface
     */
    private $inner;

    /**
     * @var BaseFilterForm
     */
    private $form;

    /**
     * FilterFormBuilder constructor.
     * @param FormBuilderInterface $inner
     */
    public function __construct(FormBuilderInterface $inner)
    {
        $this->inner = $inner;
    }

    /**
     * {@inheritdoc}
     */
    public function satisfy($constraint, $message = null, bool $append = true): static
    {
        $this->inner->satisfy($constraint, $message, $append);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function transformer($transformer, bool $append = true): static
    {
        $this->inner->transformer($transformer, $append);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function value($value): static
    {
        $this->inner->value($value);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param non-empty-string $name
     *
     * @template E as \Bdf\Form\ElementInterface
     * @psalm-param class-string<E> $element
     *
     * @return FilterChildBuilder|ElementBuilderInterface
     * @psalm-return FilterChildBuilder<ElementBuilderInterface<E>>
     */
    public function add(string $name, string $element): ChildBuilderInterface
    {
        return new FilterChildBuilder($this->inner->add($name, $element));
    }

    /**
     * {@inheritdoc}
     *
     * @param non-empty-string $name
     *
     * @return FilterChildBuilder|StringElementBuilder
     * @psalm-return FilterChildBuilder<StringElementBuilder>
     */
    public function string(string $name, ?string $default = null): ChildBuilderInterface
    {
        return new FilterChildBuilder($this->inner->string($name, $default));
    }

    /**
     * {@inheritdoc}
     *
     * @param non-empty-string $name
     *
     * @return FilterChildBuilder|IntegerElementBuilder
     * @psalm-return FilterChildBuilder<IntegerElementBuilder>
     */
    public function integer(string $name, ?int $default = null): ChildBuilderInterface
    {
        return new FilterChildBuilder($this->inner->integer($name, $default));
    }

    /**
     * {@inheritdoc}
     *
     * @param non-empty-string $name
     *
     * @return FilterChildBuilder|FloatElementBuilder
     * @psalm-return FilterChildBuilder<FloatElementBuilder>
     */
    public function float(string $name, ?float $default = null): ChildBuilderInterface
    {
        return new FilterChildBuilder($this->inner->float($name, $default));
    }

    /**
     * {@inheritdoc}
     *
     * @param non-empty-string $name
     *
     * @return FilterChildBuilder|BooleanElementBuilder
     * @psalm-return FilterChildBuilder<BooleanElementBuilder>
     */
    public function boolean(string $name): ChildBuilderInterface
    {
        return new FilterChildBuilder($this->inner->boolean($name));
    }

    /**
     * {@inheritdoc}
     *
     * @param non-empty-string $name
     *
     * @return FilterChildBuilder|DateTimeElementBuilder
     * @psalm-return FilterChildBuilder<DateTimeElementBuilder>
     */
    public function dateTime(string $name): ChildBuilderInterface
    {
        return new FilterChildBuilder($this->inner->dateTime($name));
    }

    /**
     * {@inheritdoc}
     *
     * @param non-empty-string $name
     *
     * @return FilterChildBuilder|PhoneElementBuilder
     * @psalm-return FilterChildBuilder<PhoneElementBuilder>
     */
    public function phone(string $name): ChildBuilderInterface
    {
        return new FilterChildBuilder($this->inner->phone($name));
    }

    /**
     * {@inheritdoc}
     *
     * @param non-empty-string $name
     *
     * @return FilterChildBuilder|AnyElementBuilder
     * @psalm-return FilterChildBuilder<AnyElementBuilder>
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    public function any(string $name): ChildBuilderInterface
    {
        return new FilterChildBuilder($this->inner->any($name));
    }

    /**
     * {@inheritdoc}
     *
     * @param non-empty-string $name
     * @param class-string<UnitEnum> $enumClass
     * @return FilterChildBuilder<EnumElementBuilder>
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    public function enum(string $name, string $enumClass): ChildBuilderInterface
    {
        return new FilterChildBuilder($this->inner->enum($name, $enumClass));
    }

    /**
     * {@inheritdoc}
     */
    public function csrf(string $name = '_token'): ChildBuilderInterface
    {
        return $this->inner->csrf($name);
    }

    /**
     * {@inheritdoc}
     *
     * @param non-empty-string $name
     *
     * @return FilterChildBuilder|FormBuilderInterface
     * @psalm-return FilterChildBuilder<\Bdf\Form\Aggregate\FormBuilder>
     */
    public function embedded(string $name, ?callable $configurator = null): ChildBuilderInterface
    {
        return new FilterChildBuilder($this->inner->embedded($name, $configurator));
    }

    /**
     * {@inheritdoc}
     *
     * @param non-empty-string $name
     *
     * @psalm-param class-string<ElementInterface>|null $elementType
     *
     * @return FilterChildBuilder|ArrayElementBuilder
     * @psalm-return FilterChildBuilder<ArrayElementBuilder>
     */
    public function array(string $name, ?string $elementType = null, ?callable $elementConfigurator = null): ChildBuilderInterface
    {
        return new FilterChildBuilder($this->inner->array($name, $elementType, $elementConfigurator));
    }

    /**
     * {@inheritdoc}
     */
    public function submit(string $name): ButtonBuilderInterface
    {
        return $this->inner->submit($name);
    }

    /**
     * {@inheritdoc}
     */
    public function propertyAccessor(PropertyAccessorInterface $propertyAccessor): FormBuilderInterface
    {
        $this->inner->propertyAccessor($propertyAccessor);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function validator(ValidatorInterface $validator): FormBuilderInterface
    {
        $this->inner->validator($validator);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function generator(ValueGeneratorInterface $generator): FormBuilderInterface
    {
        $this->inner->generator($generator);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function generates($entity): FormBuilderInterface
    {
        $this->inner->generates($entity);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function buildElement(): ElementInterface
    {
        return $this->inner->buildElement();
    }

    /**
     * {@inheritdoc}
     */
    public function optional(bool $flag = true): FormBuilderInterface
    {
        $this->inner->optional($flag);

        return $this;
    }

    /**
     * Forward call to inner builder, and rematch the return type
     *
     * @param string $method
     * @param array $arguments
     *
     * @return $this|FilterChildBuilder|mixed
     */
    public function __call(string $method, array $arguments): mixed
    {
        $return = $this->inner->$method(...$arguments);

        if ($return === $this->inner) {
            return $this;
        }

        if ($return instanceof ChildBuilderInterface) {
            return new FilterChildBuilder($return);
        }

        return $return;
    }

    /**
     * Create a like criteria
     *
     * @param non-empty-string $name The field name
     * @param string|null $default Default field value
     *
     * @return FilterChildBuilder<StringElementBuilder>
     *
     * @see FilterChildBuilder::like()
     */
    public function search(string $name, ?string $default = null): FilterChildBuilder
    {
        return $this->string($name, $default)->like();
    }

    /**
     * Create a like criteria for perform a "starts with" search
     *
     * @param non-empty-string $name The field name
     * @param string|null $default Default field value
     *
     * @return FilterChildBuilder<StringElementBuilder>
     *
     * @see FilterChildBuilder::startWith()
     */
    public function searchBegins(string $name, ?string $default = null): FilterChildBuilder
    {
        return $this->string($name, $default)->startWith();
    }

    /**
     * Create a like criteria for perform a "contains" search
     *
     * @param non-empty-string $name The field name
     * @param string|null $default Default field value
     *
     * @return FilterChildBuilder<StringElementBuilder>
     *
     * @see FilterChildBuilder::contains()
     */
    public function searchContains(string $name, ?string $default = null): FilterChildBuilder
    {
        return $this->string($name, $default)->contains();
    }

    /**
     * Configure field "page" for pagination
     *
     * @param non-empty-string $name Page field name. Default to "page"
     *
     * @return FilterChildBuilder<IntegerElementBuilder>
     * @psalm-suppress UndefinedMagicMethod
     */
    public function page(string $name = 'page'): FilterChildBuilder
    {
        /** @var FilterChildBuilder<IntegerElementBuilder> */
        return $this->integer($name)
            ->filter(function ($value) {
                if (!is_numeric($value)) {
                    return 1;
                }

                return max(1, (int) $value);
            })
            ->setter('page')
        ;
    }

    /**
     * Configure field "perPage" for pagination
     *
     * @param non-empty-string $name Per page field name. Default to "perPage"
     * @param int $default Default row count. Default to 10
     *
     * @return FilterChildBuilder<IntegerElementBuilder>
     * @psalm-suppress UndefinedMagicMethod
     */
    public function perPage(string $name = 'perPage', int $default = 10): FilterChildBuilder
    {
        /** @var FilterChildBuilder<IntegerElementBuilder> */
        return $this->integer($name)
            ->filter(function ($value) use ($default) {
                if (!is_numeric($value)) {
                    return $default;
                }

                return max(1, (int) $value);
            })
            ->setter('pageMaxRows')
        ;
    }
}
