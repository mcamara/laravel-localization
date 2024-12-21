<?php

namespace Mcamara\LaravelLocalization\Tests;

use Illuminate\Database\Eloquent\Model;
use Mcamara\LaravelLocalization\Interfaces\LocalizedUrlRoutable;

class ModelWithTranslatableRoutes extends Model implements LocalizedUrlRoutable
{
    public function getLocalizedRouteKey(string $locale): string
    {
        if($locale == 'es'){
            return 'empresa';
        }

        return 'company';
    }
}
