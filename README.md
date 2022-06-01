
# Laravel Magic

![GitHub last commit](https://img.shields.io/github/last-commit/dominiquevienne/laravel_magic?style=flat-square)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/dominiquevienne/laravel-magic.svg?style=flat-square)](https://packagist.org/packages/dominiquevienne/laravel-magic)
![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/dominiquevienne/laravel-magic?style=flat-square)
![GitHub top language](https://img.shields.io/github/languages/top/dominiquevienne/laravel_magic?style=flat-square)
![Packagist License](https://img.shields.io/packagist/l/dominiquevienne/laravel-magic?style=flat-square)
![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/dominiquevienne/laravel_magic?style=flat-square)
![GitHub all releases](https://img.shields.io/github/downloads/dominiquevienne/laravel_magic/total?style=flat-square)
![Packagist Stars](https://img.shields.io/packagist/stars/dominiquevienne/laravel-magic?style=flat-square)

Laravel Magic provides Abstract Controller, Model, generic Request, Traits, Exceptions and various middlewares in order to generate very easily and quickly API resources from scratch. 

## Support us

Laravel Magic is a free open source project. If you use the project, please [star us on Github](https://github.com/dominiquevienne/laravel_magic)... it costs nothing but a click! 😉

## Installation

You can install the package via composer:

```bash
composer require dominiquevienne/laravel-magic
```

This package does not provide
- any migration
- any configuration file 
- any view


## Usage

### Models

Extending your models from `AbstractModel` will give you the opportunity to get the autocompletion that comes with PHPDoc in your IDE and will make it possible to check if a relationship exist for the given model.

It will also make it possible for `AbstractController` to make the magic happen. (see Controllers). 
```php
<?php

namespace App\Models;

use Dominiquevienne\LaravelMagic\Models\AbstractModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * Your PHPDoc goes here 
 */
class YourModel extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
    // ...
    ];
}

```

### Controllers

Extending your controllers from `AbstractController` will give you the opportunity to get a fully automated way to generate index, show, destroy, update, store methods without writing a single controller line. This will fill 90% of the API Resource needs.

Since default methods are already available in `AbstractController`, you can directly configure your routes to target the usual methods and magic will happen.

```php
<?php

namespace App\Http\Controllers;

use Dominiquevienne\LaravelMagic\Http\Controllers\AbstractController;

class YourController extends AbstractController
{
}

```

#### Relationships
When calling an index or show endpoint, you can add the `with` GET parameter in order to retrieve direct relationships of a given model.
The API will not throw an error / warning if relationship is not available.

If the property is not provided or if value is empty after sanitization, the query will result in a normal query without any relationship retrieval.

#### Fields
When calling an index or show endpoint, you can add the `fields` GET parameter in order to only retrieve the listed fields.
The API will not throw an error / warning if field is not available.

If the property is not provided or if value is empty after sanitization, the query will throw every available fields.

#### Filtering
When calling an index endpoint, you can add the `filter` GET parameter in order to filter the list so you only retrieve the row you targeted.
The API will not throw an error / warning if the fields you use for filtering are not available.

If the property is not provided or if value is empty after sanitization, the query will result in a normal query retrieving all the rows.

#### Ordering
When calling an index endpoint, you can add the `filter` GET parameter in order to order the provided result is ordered the way you need.

The API will throw an error if you provide a non-compliant string.

If the property is not provided, the query will result in a normal query retrieving all the rows without any specific sorting.

#### Pagination

The standard Laravel pagination is integrated. Please refer to the official `paginate` [documentation](https://laravel.com/docs/9.x/pagination).

#### Query Caching

By default Laravel Magic will cache queries used for Index and Show methods. It will use their fingerprint to ensure that the cache value is the one corresponding to a unique query. This caching method takes in consideration the in-app filtering, user-filtering, fields, ordering, relationships and pagination. 

The default behaviour is to store the query result in cache for 8 hours but the value can be overridden in your `.env` file through the `LARAVEL_MAGIC_CACHE_DEFAULT_DURATION` variable. The value of this variable is TTL for cache in seconds. Use `LARAVEL_MAGIC_CACHE_DEFAULT_DURATION=0` if you want to avoid totally query caching.   

### Requests

Laravel Magic provides a `BootstrapRequest` file which will be call on any `AbstractConctroller.create` and `AbstractConctroller.update` methods. If a request which name is `ModelnameRequest` is available in your `Requests` folder, it will be used to generate the validation rules.

### Filtering queries

In your development, if you create a `src/Http/Filters/ModelNameFilter.php` class extending the `GenericFilter` class, Laravel Magic will automatically filter the way it retrieves data in your CRUD methods. 
If this class has not been created, by default, no filtering is done. However, if you set `LARAVEL_MAGIC_FILTERING_MODE=paranoid` in your `.env` file, LaravelMagic will make it impossible to get any data. 

### Middlewares

Laravel Magic provides `ForceJson` and `VerifyJwtToken` middlewares. Those can be set up in Laravel `Kernel`.

### Traits

We also provide a `HasPublicationStatus` trait. To use the trait, add it to your models and migrate your schema so it has the `publication_status_id` field. Value of this field is handled by `$publicationStatusAvailable` property. 

### Exceptions

Laravel Magic provides those Exceptions:
- ControllerAutomationException
- EnvException
- PublicationStatusException
- StatusUnknownException

These are used internally but you can of course use them as you want.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Dominique Vienne](https://github.com/dominiquevienne)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
