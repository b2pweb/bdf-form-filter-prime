<?php

namespace Bdf\Form\Filter;

use Bdf\Form\PropertyAccess\AbstractAccessor;
use Bdf\Form\PropertyAccess\HydratorInterface;
use Bdf\Form\PropertyAccess\Setter;
use Bdf\Prime\Entity\Criteria as PrimeCriteria;
use TypeError;

/**
 * Hydrate a prime criteria object
 * Works like Setter class, but with Criteria object instead of simple object properties
 *
 * Note: The hydration process is ignored if the field value is empty
 *
 * <code>
 * // Use the child name as property name
 * $builder->hydrator(new Criteria());
 *
 * // The property name is "myProp"
 * $builder->hydrator(new Criteria('myProp'));
 *
 * // Apply a transformation to the value
 * $builder->hydrator(new Criteria(function ($value, ChildInterface $input) {
 *    return doTransform($value);
 * }));
 *
 * // Define property name and transformer
 * $builder->hydrator(new Criteria('myProp', function ($value, ChildInterface $input) {
 *    return doTransform($value);
 * }));
 *
 * // Use a custom accessor. $mode is equals to HydratorInterface::HYDRATION
 * $builder->hydrator(new Criteria(null, null, function (PrimeCriteria $criteria, $value, $mode, Criteria $hydrator) {
 *    return $criteria->add('foo', $this->myCustomTransformation($value));
 * }));
 * </code>
 *
 * @see \Bdf\Prime\Entity\Criteria The handled type
 * @see Setter
 */
final class Criteria extends AbstractAccessor implements HydratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function hydrate(&$criteria)
    {
        if (!$criteria instanceof PrimeCriteria) {
            throw new TypeError('$criteria must be an instance of '.PrimeCriteria::class);
        }

        $value = $this->input->element()->value();

        if ($this->isEmpty($value)) {
            return;
        }

        if ($this->transformer) {
            $value = ($this->transformer)($value, $this->input);
        }

        if ($this->customAccessor !== null) {
            ($this->customAccessor)($criteria, $value, self::HYDRATION, $this);
        } else {
            $criteria->add($this->getPropertyName(), $value);
        }
    }

    /**
     * Is the value empty for a form
     *
     * @param mixed $value
     *
     * @return bool
     */
    private function isEmpty($value): bool
    {
        if ($value === null) {
            return true;
        }

        switch (gettype($value)) {
            case 'string':
                return strlen($value) === 0;

            case 'integer':
            case 'double':
            case 'boolean':
                return false;

            default:
                return empty($value);
        }
    }
}
