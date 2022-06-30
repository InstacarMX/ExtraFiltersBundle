# Instacar\ExtraFiltersBundle
A Symfony Bundle for API Platform 2.6 to add a powerful filter based on Symfony Expressions, with support for virtual 
fields and composable filters.

## Before you go
This is a WIP (Work-In-Progress), so you must expect breaking changes with the release of a new version. This software
will try to stick with the semver conventions (trying to don't introduce backward-incompatible changes with the
release of new patch version), but I don't provide support for old versions.

## Scope
This bundle is NOT:
- A client-side expression builder. You must define the properties and the expressions beforehand. If you want it, you can
  use [this awesome bundle by metaclass](https://github.com/metaclass-nl/filter-bundle/tree/query-expression-generator)
  (but it can open DDoS vectors in public APIs, [see this comment](https://github.com/api-platform/core/pull/2055#issuecomment-405308524)).

## Installation
Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Applications that use Symfony Flex
Open a command console, enter your project directory and execute:

```shell
composer require instacar/composable-filter-bundle:dev-filter-adapter
```

That's all! You can jump right to "Configuration".

### Applications that don't use Symfony Flex
#### Step 1: Download the Bundle
Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```shell
composer require instacar/extra-filters-bundle:dev-filter-adapter
```

#### Step 2: Enable the Bundle
Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    Instacar\ExtraFiltersBundle\InstacarExtraFiltersBundle::class => ['all' => true],
];
```

## Usage
You can implement the ExpressionFilter as a normal filter for API Platform, but you must pass in the arguments the
filters that the expression language have access to. For example:

```php
// src/Entity/Book.php

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\ORM\Mapping as ORM;
use Instacar\ExtraFiltersBundle\Doctrine\Orm\Filter\ExpressionFilter;

#[ApiResource]
#[ApiFilter(ExpressionFilter::class, properties: [
    'search' => 'orWhere(search("name"), search("author.name"), search("year"))',
    'arguments' => [
        'filters' => [
            SearchFilter::class,
        ]
    ]
])]
#[ORM\Entity]
class Book {
    // real implementation
}
```

The expression syntax are the following:
```php
['filter-property' => 'operator(filter1(property, strategy, value), filter2(property, strategy, value), ..., filterN(property, strategy, value))'];
```

Where:
- **filter-property:** The property used for filter in your API. You can use a virtual property (a property that is not
  present in your entity).
- **operator:** A [supported operator](#supported-operators).
- **filter:** A [supported filter](#supported-filters).
- **property:** The name of the property from the entity. You can use "property" if you want to use the same name from
  the filter-property.
- **strategy:** The strategy from the documented values in the filter's documentation. Optional. You can use "null" for
  use the follow parameter.
- **value:** The value passed to the filter. Optional. You can manipulate the value before pass it to the filter with 
  this property, for example with the DateFilter you can search old dates with `{before: value}`.

### Supported operators
- **andWhere:** Equal to the SQL operator "AND".
- **orWhere:** Equal to the SQL operator "OR".
- **notWhere:** Equal to the SQL operator "NOT".

### Supported filters
- All the filters for API Platform for the ORM (currently tested SearchFilter and DateFilter).
- Custom filters that implement the interface `ContextAwareFilterInterface` for the ORM.
Note: The filter's name for the expression is in camelCase without the "Filter" suffix (for example, SearchFilter is
converted to search).

## Limitations
- It only works with the ORM filters.
- It does not generate a tailored documentation for API Platform, it only generates a generic property with the "string" value.

## Future work
These are the list of the ideas that I have for this bundle. If you have another idea, let me know in the "Issues" tab.
- Working ODM filters with this filter.
- A simplified filter usage with auto-register.

## Licensing
This bundle is licensed under the GNU LGPLv3. For a quick resume of the permissions with this license see the
[GNU LGPLv3](https://choosealicense.com/licenses/lgpl-3.0/) in [choosealicense.com](https://choosealicense.com).

See the [LICENSE](LICENSE.md) file for more details.
