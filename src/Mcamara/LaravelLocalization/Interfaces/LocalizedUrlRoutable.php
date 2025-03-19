<?php

namespace Mcamara\LaravelLocalization\Interfaces;

interface LocalizedUrlRoutable
{
    /**
     * Get the value of the model's localized route key.
     */
    public function getLocalizedRouteKey(string $locale): string;
}
