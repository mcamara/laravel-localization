<?php

use Illuminate\Support\Facades\Route;

if (!function_exists('localized_trans_route')) {
    function localized_trans_route(string $name, array $parameters = [], string|null $locale = null): string
    {
        return \Mcamara\LaravelLocalization\Facades\LaravelLocalization::transRoute($name, $parameters, $locale);
    }
}

if (!function_exists('localized_route')) {
    function localized_route(string $name, array $parameters = [], string|null $locale = null): string
    {
        return \Mcamara\LaravelLocalization\Facades\LaravelLocalization::route($name, $parameters, $locale);
    }
}
