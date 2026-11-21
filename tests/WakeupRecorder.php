<?php

namespace Mcamara\LaravelLocalization\Tests;

class WakeupRecorder
{
    /**
     * Whether unserialize() woke up an instance of this class.
     */
    public static bool $wokenUp = false;

    public function __wakeup(): void
    {
        static::$wokenUp = true;
    }
}
