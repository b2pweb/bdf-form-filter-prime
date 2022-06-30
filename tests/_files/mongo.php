<?php

namespace Bdf\Form\Filter;

use Bdf\Prime\MongoDB\Document\DocumentMapper;

class PersonDocumentFormFilter extends MongoFilterForm
{
    protected function configureFilters(FilterFormBuilder $builder): void
    {
        $this->setDocument(PersonDocument::class);

        $builder->string('firstName')->startWith();
        $builder->searchBegins('lastName');
        $builder->embedded('age', function ($builder) {
            $builder->integer('0')->setter();
            $builder->integer('1')->setter();
        })->between();
    }
}

class PersonDocument
{
    public ?string $firstName = null;
    public ?string $lastName = null;
    public ?int $age = null;
}

class PersonDocumentMapper extends DocumentMapper
{
    public function connection(): string
    {
        return 'mongo';
    }

    public function collection(): string
    {
        return 'person';
    }
}
