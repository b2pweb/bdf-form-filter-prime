# Form filter for Prime

Helper library for BDF Form to create Prime filters.

[![Build Status](https://travis-ci.org/b2pweb/bdf-form-filter-prime.svg?branch=master)](https://travis-ci.org/b2pweb/bdf-form-filter-prime)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/b2pweb/bdf-form-filter-prime/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/b2pweb/bdf-form-filter-prime/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/b2pweb/bdf-form-filter-prime/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/b2pweb/bdf-form-filter-prime/?branch=master)
[![Packagist Version](https://img.shields.io/packagist/v/b2pweb/bdf-form-filter-prime.svg)](https://packagist.org/packages/b2pweb/bdf-form-filter-prime)
[![Total Downloads](https://img.shields.io/packagist/dt/b2pweb/bdf-form-filter-prime.svg)](https://packagist.org/packages/b2pweb/bdf-form-filter-prime)
[![Type Coverage](https://shepherd.dev/github/b2pweb/bdf-form-filter-prime/coverage.svg)](https://shepherd.dev/github/b2pweb/bdf-form-filter-prime)

See :
- [BDF Form](https://github.com/b2pweb/bdf-form)
- [Prime](https://github.com/b2pweb/bdf-prime)

## Installation using composer

```
composer require b2pweb/bdf-form-form-filter-prime
```

## Basic usage

To create a form filter, simply extends the class [`FilterForm`](src/FilterForm.php) and implements method `CustomForm::configureFilters()` :

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
