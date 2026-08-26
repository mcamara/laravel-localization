<?php

namespace Mcamara\LaravelLocalization\Tests;

use Illuminate\Database\Eloquent\Model;

class ModelWithCustomRouteKey extends Model
{
    protected $fillable = ['slug'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
