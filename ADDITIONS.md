# Additional information

## Installation

### For Laravel 5.4 and below:

For older versions of the framework, follow the steps below:

Register the service provider in `config/app.php`

```php
        'providers' => [
		// [...]
                Mcamara\LaravelLocalization\LaravelLocalizationServiceProvider::class,
        ],
```

You may also register the `LaravelLocalization` facade:

```php
        'aliases' => [
		// [...]
                'LaravelLocalization' => Mcamara\LaravelLocalization\Facades\LaravelLocalization::class,
        ],
```
