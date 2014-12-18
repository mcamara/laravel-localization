<?php

namespace Mcamara\LaravelLocalization;


use Exception;

class SupportedLocalesNotDefined extends Exception{


    function __construct()
    {
        parent::__construct("Supported locales must be defined.");
    }
}
