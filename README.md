# Instacar\ExtraFiltersBundle
A Symfony Bundle for API Platform 2.6 to add a powerful filter based on Symfony Expressions, with support to virtual 
fields and composable filters.

## Before you go
This is a WIP (Work-In-Progress), so you must expect breaking changes with the release of a new version. This software
will try to stick with the semver conventions (trying to don't introduce backward-incompatible changes with the
release of new patch version), but I don't provide support for old versions.

## Installation
Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Applications that use Symfony Flex
Open a command console, enter your project directory and execute:

```shell
composer require instacar/composable-filter-bundle
```

That's all! You can jump right to "Configuration".

### Applications that don't use Symfony Flex
#### Step 1: Download the Bundle
Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```shell
composer require instacar/extra-filters-bundle
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
TODO

## Licensing
This bundle is licensed under the GNU GPLv3. For a quick resume of the permissions with this license see the
[GNU LGPLv3](https://choosealicense.com/licenses/lgpl-3.0/) in [choosealicense.com](https://choosealicense.com).

See the [LICENSE](LICENSE.md) file for more details.
