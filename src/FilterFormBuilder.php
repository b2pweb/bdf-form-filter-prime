<?php


namespace Bdf\Form\Filter;

use Bdf\Form\Aggregate\ArrayElementBuilder;
use Bdf\Form\Aggregate\FormBuilderInterface;
use Bdf\Form\Aggregate\Value\ValueGeneratorInterface;
use Bdf\Form\Button\ButtonBuilderInterface;
use Bdf\Form\Child\ChildBuilderInterface;
use Bdf\Form\ElementBuilderInterface;
use Bdf\Form\ElementInterface;
use Bdf\Form\Leaf\BooleanElementBuilder;
use Bdf\Form\Leaf\Date\DateTimeElementBuilder;
use Bdf\Form\Leaf\FloatElementBuilder;
use Bdf\Form\Leaf\IntegerElementBuilder;
use Bdf\Form\Leaf\StringElementBuilder;
use Bdf\Form\Phone\PhoneElementBuilder;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
    public function satisfy($constraint, $options = null, bool $append = true)
    {
        $this->inner->satisfy($constraint, $options, $append);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function transformer($transformer, bool $append = true)
    {
        $this->inner->transformer($transformer, $append);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function value($value)
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
     * Forward call to inner builder, and rematch the return type
     *
     * @param string $method
     * @param array $arguments
     *
     * @return $this|FilterChildBuilder|mixed
     */
    public function __call(string $method, array $arguments)
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
     * @return FilterChildBuilder|StringElementBuilder
     *
     * @see FilterChildBuilder::like()
     */
    public function search(string $name, ?string $default = null)
    {
        return $this->string($name, $default)->like();
    }

    /**
     * Create a like criteria for perform a "starts with" search
     *
     * @param non-empty-string $name The field name
     * @param string|null $default Default field value
     *
     * @return FilterChildBuilder|StringElementBuilder
     *
     * @see FilterChildBuilder::startWith()
     */
    public function searchBegins(string $name, ?string $default = null)
    {
        return $this->string($name, $default)->startWith();
    }

    /**
     * Create a like criteria for perform a "contains" search
     *
     * @param non-empty-string $name The field name
     * @param string|null $default Default field value
     *
     * @return FilterChildBuilder|StringElementBuilder
     *
     * @see FilterChildBuilder::contains()
     */
    public function searchContains(string $name, ?string $default = null)
    {
        return $this->string($name, $default)->contains();
    }
}
