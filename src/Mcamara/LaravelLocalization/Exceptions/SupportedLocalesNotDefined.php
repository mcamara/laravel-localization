<?php

namespace Mcamara\LaravelLocalization\Exceptions;

use Exception;

class SupportedLocalesNotDefined extends Exception
{
    public function __construct()
    {
        parent::__construct('Supported locales must be defined.');
    }
}
