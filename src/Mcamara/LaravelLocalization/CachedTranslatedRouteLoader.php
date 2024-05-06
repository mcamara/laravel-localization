<?php

namespace Mcamara\LaravelLocalization;

use Mcamara\LaravelLocalization\Traits\LoadsTranslatedCachedRoutes;

class CachedTranslatedRouteLoader
{
    use LoadsTranslatedCachedRoutes;


    /**
     * Laravel application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;


    public function __construct(\Illuminate\Contracts\Foundation\Application $app)
    {
        $this->app = $app;
    }


    public function __invoke()
    {
        $this->loadCachedRoutes();
    }
}