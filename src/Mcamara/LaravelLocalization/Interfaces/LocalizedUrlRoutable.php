<?php

namespace Mcamara\LaravelLocalization\Interfaces;

interface LocalizedUrlRoutable
{
    /**
     * Get the value of the model's localized route key.
     *
     * @return mixed
     */
    public function getLocalizedRouteKey($locale);
}
