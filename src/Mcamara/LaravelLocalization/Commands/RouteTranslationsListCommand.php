<?php

namespace Mcamara\LaravelLocalization\Commands;

use Mcamara\LaravelLocalization\LaravelLocalization;
use Mcamara\LaravelLocalization\Traits\TranslatedRouteCommandContext;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Console\RouteListCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;

class RouteTranslationsListCommand extends RouteListCommand
{
    use TranslatedRouteCommandContext;

    /**
     * @var string
     */
    protected $name = 'route:trans:list';

    /**
     * @var string
     */
    protected $description = 'List all registered routes for specific locales';


    /**
     * Configure the console command using a fluent definition.
     *
     * Laravel 13.24 moved RouteListCommand to a `$signature`, which takes precedence over
     * `$name`, so without this the command registers itself as `route:list`. Rewriting the
     * inherited signature keeps the parent's options instead of restating them; `locale`
     * moves in here because `getArguments()` is not consulted on this path.
     *
     * @return void
     */
    #[\Override]
    protected function configureUsingFluentDefinition()
    {
        $this->signature = Str::replaceFirst('route:list', $this->name, $this->signature)
            . ' {locale : The locale to list routes for.}';

        parent::configureUsingFluentDefinition();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $locale = $this->argument('locale');

        if ( ! $this->isSupportedLocale($locale)) {
            $this->error("Unsupported locale: '{$locale}'.");
            return;
        }

        $this->loadFreshApplicationRoutes($locale);

        parent::handle();
    }

    /**
     * Boot a fresh copy of the application and replace the router/routes.
     *
     * @param string $locale
     * @return void
     */
    protected function loadFreshApplicationRoutes($locale)
    {
        $app = require $this->getBootstrapPath() . '/app.php';

        $key = LaravelLocalization::ENV_ROUTE_KEY;

        putenv("{$key}={$locale}");

        $app->make(Kernel::class)->bootstrap();

        putenv("{$key}=");

        $this->router = $app['router'];
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['locale', InputArgument::REQUIRED, 'The locale to list routes for.'],
        ];
    }
}
