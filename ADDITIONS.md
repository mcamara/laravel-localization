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

## Config

### Service Providers

Otherwise, you can use `ConfigServiceProviders` (check <a href="https://raw.githubusercontent.com/mcamara/laravel-localization/master/src/config/config.php">this file</a> for more info).

For example, editing the default config service provider that Laravel loads when it's installed. This file is placed in `app/providers/ConfigServicePovider.php` and would look like this:

```php
<?php namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider {
	public function register()
	{
		config([
			'laravellocalization.supportedLocales' => [
				'ace' => array( 'name' => 'Achinese', 'script' => 'Latn', 'native' => 'Aceh' ),
				'ca'  => array( 'name' => 'Catalan', 'script' => 'Latn', 'native' => 'català' ),
				'en'  => array( 'name' => 'English', 'script' => 'Latn', 'native' => 'English' ),
			],

			'laravellocalization.useAcceptLanguageHeader' => true,

			'laravellocalization.hideDefaultLocaleInURL' => true
		]);
	}

}
```

This config would add Catalan and Achinese as languages and override any other previous supported locales and all the other options in the package.

You can create your own config providers and add them on your application config file (check the providers array in `config/app.php`).
