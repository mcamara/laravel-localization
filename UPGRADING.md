## From v1 to v2
This package now uses [dependency injection](https://laravel.com/docs/container#introduction) to retrieve dependencies from the container.

This modification is a breaking change, especially if you had made extensions to the `__construct` method within the `Mcamara\LaravelLocalization\LaravelLocalization` class.
You may now use depdency injection in your own implementation and forward the dependencies to the parent constructor.
```php
use Mcamara\LaravelLocalization\LaravelLocalization;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;

class MyLaravelLocalization extends LaravelLocalization
{
    public function __construct(
        mixed $myCustomVariable,
        Application $app,
        ConfigRepository $configRepository,
        Translator $translator,
        Router $router,
        Request $request,
        UrlGenerator $url
    ) {
        parent::__construct($app, $configRepository, $translator, $router, $request, $url);
    }
}
```

If your previous approach involved overriding the `LaravelLocalization` singleton in the container and generating a new instance of your custom implementation, there's now a more straightforward method for binding. This will automatically inject the correct dependencies for you.
```diff
use Mcamara\LaravelLocalization\LaravelLocalization;

-$this->app->singleton(LaravelLocalization::class, function () {
-    return new MyLaravelLocalization();
-});
+$this->app->singleton(LaravelLocalization::class, MyLaravelLocalization::class);
```

For more information, please see the following PR [#879](https://github.com/mcamara/laravel-localization/pull/879/files)