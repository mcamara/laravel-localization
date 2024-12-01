<?php

namespace Mcamara\LaravelLocalization\Tests;

use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Mcamara\LaravelLocalization\LaravelLocalizationServiceProvider;
use Orchestra\Testbench\BrowserKit\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [LaravelLocalizationServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'LaravelLocalization' => LaravelLocalization::class,
        ];
    }
}
