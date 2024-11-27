<?php

namespace Mcamara\LaravelLocalization\Tests;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Mcamara\LaravelLocalization\LaravelLocalization;

class CustomTranslatorTest extends TestCase
{
    // The LaravelLocalization class supports the use of a custom translator instance.
    // When the setLocale() method is invoked, it delegates the operation to the application instance,
    // which, in turn, updates the locale on the translator instance bound in the service container.
    // If a custom translator is provided, LaravelLocalization ensures that the locale is also set correctly
    // on the custom translator to maintain consistent behavior across the application.
    public function testCanSetLocaleForCustomTranslator(): void
    {
        $loader = new ArrayLoader();
        $translator = new Translator($loader , 'en');
        $localization = new LaravelLocalization(
            $this->app,
            $this->app['config'],
            $translator,
            $this->app['router'],
            $this->app['request'],
            $this->app['url']
        );

        $localization->setLocale('es');

        $this->assertEquals('es', $translator->getLocale());
    }
}
