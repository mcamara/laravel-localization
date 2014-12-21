<?php

namespace Mcamara\LaravelLocalization\Exceptions;


use Exception;

class SupportedLocalesNotDefined extends Exception {


    function __construct()
    {
        parent::__construct("Supported locales must be defined.");
    }
}
