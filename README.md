# Form filter for Prime

Helper library for BDF Form to create Prime filters.

[![build](https://github.com/b2pweb/bdf-form-filter-prime/actions/workflows/php.yml/badge.svg)](https://github.com/b2pweb/bdf-form-filter-prime/actions/workflows/php.yml)
[![codecov](https://codecov.io/github/b2pweb/bdf-form-filter-prime/branch/master/graph/badge.svg?token=VOFSPEWYKX)](https://app.codecov.io/github/b2pweb/bdf-form-filter-prime)
[![Packagist Version](https://img.shields.io/packagist/v/b2pweb/bdf-form-filter-prime.svg)](https://packagist.org/packages/b2pweb/bdf-form-filter-prime)
[![Total Downloads](https://img.shields.io/packagist/dt/b2pweb/bdf-form-filter-prime.svg)](https://packagist.org/packages/b2pweb/bdf-form-filter-prime)
[![Type Coverage](https://shepherd.dev/github/b2pweb/bdf-form-filter-prime/coverage.svg)](https://shepherd.dev/github/b2pweb/bdf-form-filter-prime)

See :
- [BDF Form](https://github.com/b2pweb/bdf-form)
- [Prime](https://github.com/b2pweb/bdf-prime)

## Installation using composer

```
composer require b2pweb/bdf-form-filter-prime
```

## Basic usage

To create a form filter, simply extends the class [`FilterForm`](src/FilterForm.php) and implements method `FilterForm::configureFilters()` :

```php
<?php

namespace App\Form;

use Bdf\Form\Filter\FilterForm;
use Bdf\Form\Filter\FilterFormBuilder;

class MyFilters extends FilterForm
{
    public function configureFilters(FilterFormBuilder $builder): void
    {
        // Build filter fields
        // Will add a "foo LIKE xxx%"
        $builder->searchBegins('foo');

        // Will add a "age BETWEEN ? AND ?"
        $builder->embedded('age', function ($builder) {
            $builder->integer('0')->setter();
            $builder->integer('1')->setter();
        })->between();
        
        // Define a custom attribute name and operator
        $builder->string('foo')->criterion('bar')->operator('>=');
    }
}
```

Now, you can submit data to the form, apply filters to the query :

```php
<?php

// Instantiate the form (a container can be use for handle dependency injection)
$form = new MyFilters();

// Submit form
// Note: if some constraints has been added, call `$form->valid()` and `$form->error()` to check errors
$form->submit($request->query->all());

// Get generated criteria
$criteria = $form->value();

// Call prime with criteria
$list = MyEntity::where($criteria->all())->paginate();
```

## Query helpers

Two helpers methods are available to handle Prime query without use directly the `Criteria` object :
- `FilterForm::apply()` will apply the filters to the query instance
- `FirstForm::query()` will create the query with filters

```php
<?php

namespace App\Form;

use Bdf\Form\Filter\FilterForm;
use Bdf\Form\Filter\FilterFormBuilder;

class MyFilters extends FilterForm
{
    public function configureFilters(FilterFormBuilder $builder): void
    {
        // Set the entity class (note: use $this instead of $builder)
        $this->setEntity(Person::class);

        // Define filters
        $builder->searchBegins('firstName');
        $builder->searchBegins('lastName');
        $builder->embedded('age', function ($builder) {
            $builder->integer('0')->setter();
            $builder->integer('1')->setter();
        })->between();
    }
}
```

To use the helpers methods, it's necessary to inject the Prime's `ServiceLocator` instance on the constructor.

```php
<?php

// Get the form instance, using a container to inject prime
$form = $container->get(MyFilters::class);

// Submit form
$form->submit($request->query->all());

// Use apply to modify the query
$query = Person::builder();
$entities = $form->apply($query)->all(); // Apply filters and execute query

// Use directly query() method to create the filter query
$entities = $form->query();
```

## Usage with mongodb

This library also supports Prime MongoDB filter. It requires b2pweb/bdf-prime-mongodb version 2.0.
To use, simply use `Bdf\Form\Filter\MongoFilterForm` instead of `Bdf\Form\Filter\FilterForm`, and change `$form->setEntity(...)` to `$form->setDocument()`.

```php
<?php

namespace App\Form;

use Bdf\Form\Filter\MongoFilterForm;
use Bdf\Form\Filter\FilterFormBuilder;

class MyFilters extends MongoFilterForm
{
    public function configureFilters(FilterFormBuilder $builder): void
    {
        // Set the document class (note: use $this instead of $builder)
        $this->setEntity(Person::class);

        // Define filters
        $builder->searchBegins('firstName');
        $builder->searchBegins('lastName');
        $builder->embedded('age', function ($builder) {
            $builder->integer('0')->setter();
            $builder->integer('1')->setter();
        })->between();
    }
}
```

The usage is identical to basic prime entity usage.
