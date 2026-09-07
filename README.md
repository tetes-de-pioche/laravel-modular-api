# Laravel Modular API

### Key Features:

- **JSON:API Compliance:** Generate API responses that fully comply with the JSON:API specification, ensuring data
  consistency and interoperability (built on Laravel's native JSON:API resources)
- **Autonomous Services:** Structure your business logic into independent services, facilitating code reuse and clear
  separation of concerns.
- **API Versioning:** Easily version your API to manage changes and ensure backward compatibility.
- **Sub-APIs:** Create multiple sub-APIs (e.g., `public`, `protected`, `private`, ...) to handle different access
  levels and use cases.
- **Localization:** Serve multi-language content by processing a request header that automatically sets the locale for
  the entire request.
- **Obfuscated IDs:** Expose short, non-sequential identifiers instead of raw database keys, decoded transparently on
  the way in.
- **JSON:API pagination:** Paginate any Eloquent query with the `page[number]` / `page[size]` members, links included.
- **Negotiated errors:** Serve spec compliant JSON:API error objects to clients that ask for them, without breaking
  clients built against the Laravel error shape.
- **Flexibility and Extensibility:** The package is designed to be extensible, allowing you to adapt services to
  specific needs while following best development practices.

### Version support

- **PHP:** `8.3`, `8.4`
- **Laravel:** `13.0`

## Installation

You can install the package via composer:

```bash
composer require tetes-de-pioche/laravel-modular-api
```

If you want to use obfuscated ids (short, non-sequential identifiers derived from your primary keys) for your
resources, you can install the required package via composer:

```bash
composer require sqids/sqids
```

If you want to customize the configuration, you can publish the config file:

```bash
php artisan vendor:publish --tag="modular-api-config"
```

This is the contents of the published config file:

```php
return [

    /*
    |--------------------------------------------------------------------------
    | API configuration
    |--------------------------------------------------------------------------
    */

    'api' => [

        'routing' => [
            'url' => env('MODULAR_API_ROUTING_URL', env('APP_URL', 'http://localhost')),
            'url_prefix' => env('MODULAR_API_ROUTING_URL_PREFIX', '/'),
            'route_prefix' => env('MODULAR_API_ROUTING_ROUTE_PREFIX', 'api'),
            'enable_version_prefix' => env('MODULAR_API_ROUTING_ENABLE_VERSION_PREFIX', true),
            'enable_type_prefix' => env('MODULAR_API_ROUTING_ENABLE_TYPE_PREFIX', true),
        ],

        'resource' => [
            'custom_type_resolver' => env('MODULAR_API_RESOURCE_CUSTOM_TYPE_RESOLVER', false),
        ],

        'pagination' => [
            'size_default' => env('MODULAR_API_PAGINATION_SIZE_DEFAULT', 25),
            'size_max' => env('MODULAR_API_PAGINATION_SIZE_MAX', 100),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Web configuration
    |--------------------------------------------------------------------------
    */

    'web' => [

        'routing' => [
            'url' => env('MODULAR_API_WEB_ROUTING_URL', env('APP_URL', 'http://localhost')),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Modular code configuration
    |--------------------------------------------------------------------------
    */

    'services' => [

        'root_path' => env('MODULAR_API_SERVICES_ROOT_PATH', 'Services'),

    ],

    /*
    |--------------------------------------------------------------------------
    | Features configuration
    |--------------------------------------------------------------------------
    */

    'features' => [

        'rate_limiting' => [
            'enabled' => env('MODULAR_API_FEATURE_RATE_LIMITING_ENABLED', false),
            'attempts' => env('MODULAR_API_FEATURE_RATE_LIMITING_ATTEMPTS_PER_MIN', 30),
            'expires' => env('MODULAR_API_FEATURE_RATE_LIMITING_EXPIRES_IN_MIN', 1),
        ],

        'localization' => [
            'enabled' => env('MODULAR_API_FEATURE_LOCALIZATION_ENABLED', false),
            'request_header' => env('MODULAR_API_FEATURE_LOCALIZATION_REQUEST_HEADER', 'Accept-Language'),
            'locales' => env('MODULAR_API_FEATURE_LOCALIZATION_LOCALES', env('APP_LOCALE', 'en')),
        ],

        'obfuscated_ids' => [
            'enabled' => env('MODULAR_API_FEATURE_OBFUSCATED_IDS_ENABLED', false),
            'key' => env('MODULAR_API_FEATURE_OBFUSCATED_IDS_KEY', env('APP_KEY')),
            'min_length' => env('MODULAR_API_FEATURE_OBFUSCATED_IDS_MIN_LENGTH', 20),
            'alphabet' => env('MODULAR_API_FEATURE_OBFUSCATED_IDS_ALPHABET',
                'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'),
        ],

    ],

];
```

## Getting started

Services directory structure:

```php
App/
    Services/
        DomainA/
            Service1/
                Config/
                Data/
                    Migrations/
                Http/
                    Controllers/
                    Endpoints/
                    Requests/
                    WebEndpoints/
                Mails/
                    Templates/
                Models/
                Resources/
                Providers/
                    MainServiceProvider.php
                Views/
            Service2/
                ...
        DomainB/
            Service1/
                ...
            Service2/
                ...
```

## Querying

Two builder macros implement the JSON:API request contract on any Eloquent query:

```php
// page[number] / page[size], other query parameters preserved in the links
$paginator = MyModel::query()->jsonApiPaginate($request);

// resolve one record from the identifier as exposed by the API
$myModel = MyModel::query()->jsonApiFind($id);
```

`jsonApiPaginate()` takes an optional default and maximum page size, to raise the cap on a bounded-cardinality
resource that may be fetched in a single page:

```php
MyModel::query()->jsonApiPaginate($request, perPage: 25, maxPerPage: 500);
```

`jsonApiFind()` decodes the identifier when the obfuscated ids feature is enabled, and raises a
`ResourceNotFoundException` when nothing matches.

Both are registered on `Illuminate\Database\Eloquent\Builder`, so query builder packages forwarding unknown calls to
their underlying builder — `spatie/laravel-query-builder` among them — pick them up with no extra wiring.

## Obfuscated IDs

The feature is optional: install `sqids/sqids` to use it. Enabling it without the package raises a
`FeatureInvalidException`.

When the feature is enabled, `ApiResource` exposes [Sqids](https://sqids.org) identifiers instead of raw primary keys,
and `BaseRequest` decodes them back before validation runs. Sqids has no salt of its own: the package derives a
project-specific alphabet from a deterministic, key-seeded shuffle, so two applications with different keys never
produce the same identifier for the same row.

Opt a resource out with:

```php
class MyResource extends ApiResource
{
    public static bool $useObfuscatedIds = false;
}
```

Encoding is handled by `Features\ObfuscatedIdEncoder`, bound as a singleton. Bind your own implementation to change the
strategy:

```php
$this->app->bind(ObfuscatedIdEncoder::class, MyEncoder::class);
```

## Errors

Error responses are negotiated on the `Accept` header, so adopting the package on an existing API does not break its
consumers.

A client sending `Accept: application/vnd.api+json` gets JSON:API error objects, with the matching `Content-Type`:

```json
{
  "errors": [
    {
      "status": "422",
      "title": "Unprocessable Content",
      "detail": "The email field is required.",
      "source": { "pointer": "/data/attributes/email" }
    }
  ]
}
```

Any other client keeps the Laravel shape:

```json
{
  "message": "The given data was invalid.",
  "errors": { "email": ["The email field is required."] }
}
```

`source` follows the request document assembled by `BaseRequest`: a validation key matching a route parameter is
reported as `source.parameter`, every other key as a `/data/attributes` pointer with dots as nested segments.

The package renders framework exceptions (`ValidationException`, `NotFoundHttpException`) as API responses only for
API requests, so web endpoints keep the default Laravel behaviour. Its own `BaseException` is always rendered as an
API response.

## License

The DBAD License (DBAD). Please see [License File](LICENSE.md) for more information.
