<?php

namespace Mcamara\LaravelLocalization\Middleware;

use Illuminate\Http\Request;

class LaravelLocalizationMiddlewareBase
{
    protected array|null $except = null;

    /**
     * Determine if the request has a URI that should not be localized.
     */
    protected function shouldIgnore(Request $request): bool
    {
        if (in_array($request->method(), config('laravellocalization.httpMethodsIgnored'))) {
            return true;
        }
        $this->except = $this->except ?? config('laravellocalization.urlsIgnored', []);
        foreach ($this->except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->is($except)) {
                return true;
            }
        }

        return false;
    }
}
