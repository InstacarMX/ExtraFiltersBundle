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
composer require instacar/composable-filter-bundle:dev-main
```

That's all! You can jump right to "Configuration".

### Applications that don't use Symfony Flex
#### Step 1: Download the Bundle
Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```shell
composer require instacar/extra-filters-bundle:dev-main
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
You can implement the filter just as any filter in API Platform (keep in mind that this filter is for advanced 
user-cases, if you can implement a normal filter from API Platform, use it).

```php
// src/Entity/Book.php

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Instacar\ExtraFiltersBundle\Doctrine\Orm\Filter\ExpressionFilter;

#[ApiResource]
#[ApiFilter(ExpressionFilter::class, properties: [
    'search' => 'orWhere(match("name", "ipartial"), match("author.name", "ipartial"), match("year", "exact"))',
])]
#[ORM\Entity]
class Book {
    // real implementation
}
```

The expression syntax are the following:
```php
['filter-property' => 'operator(filter1(property, strategy), filter2(property, strategy), ..., filterN(property, strategy))'];
```

You can use the variable "property" (that contains the filter property name) if you want to use the same name for 
filter the entity, and the variable "value" for manipulate the filter value.

Currently, the operations supported in the expression are:
- **match:** Equal to the filter "SearchFilter".
- **andWhere:** Equal to the SQL operator "AND".
- **orWhere:** Equal to the SQL operator "OR".
- **notWhere:** Equal to the SQL operator "NOT".
- and all the operations for the [Symfony Expression Language](https://symfony.com/doc/current/components/expression_language/syntax.html).

## Limitations
- It only has the "match" filter (a copy of the SearchFilter for API Platform).
- It does not generate a tailored documentation for API Platform, it only generates a generic property with the "string" value.

## Future work
These are the list of the ideas that I have for this bundle. If you have another idea, let me know in the "Issues" tab.
- Implement all the API Platform and the user's filters throughout a decorated Query Builder (without rewrite it).

## Licensing
This bundle is licensed under the GNU LGPLv3. For a quick resume of the permissions with this license see the
[GNU LGPLv3](https://choosealicense.com/licenses/lgpl-3.0/) in [choosealicense.com](https://choosealicense.com).

See the [LICENSE](LICENSE.md) file for more details.
