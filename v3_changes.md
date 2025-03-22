# Changes

The major architectural change, discussed in [#921](https://github.com/mcamara/laravel-localization/issues/921), shifts locale handling from the route file to middleware.

This allows us to remove the custom caching solution and rely on Laravel's default caching. However, it introduces a new workaround for translated routes—although they never worked completely bug-free in the first place.

Below is a list of functions and features removed in v3. Some code was removed due to a lack of documentation, tests, or a clear explanation in the pull request. Additionally, a lot of code appeared to be unused, and even after careful reverse engineering, its purpose remained unclear—so it was removed.

If you notice something critical missing, please **open an issue**.

The main improvement of the code is:

- We can use native caching
- Much faster
- Most of the 35 current open issues are fixed, should now be compatible with other packages
- Code base much simpler

However, this comes with a cost:

- Using `route(..)` helper does not work for translated routes (those defined in `/lang/routes.php` ). 
   You can use `localized_trans_route` helper instead.
- If you enable `hiddenDefaultLocales` and want to avoid an additional redirect for every route going to the default locale, 
   you should use `localized_route` helper instead.


## Removals & Changes

- **Removed custom caching command** – Now fully compatible with Laravel’s built-in caching.
- **Locale is now a route parameter** instead of being set directly in route definitions.
- **Removed `baseUrl` property and related methods**.
- **`getBaseUrl()` is no longer used** – If you have a valid use case, please open an issue.
- **Removed `route.translation` event** – Documentation was unclear, there were open issues, and it was inconsistently triggered (only when the URL was empty during localization).
- **The `data` attribute is no longer removed from routes attributes** – This should not be the responsibility of the package.
- **`getLocalizedURL(locale: false)` no longer removes the locale from the URL**.
- - **`getLocalizedURL()` no longer returns false if url is not found, instead, the same url is returned**.
- **Dropped alias `localizeURL`** – If needed, you can define a custom helper.
- **`translatedRoutes` is no longer stored inside `LaravelLocalization`**.
- **Removed `getNonLocalizedURL()`**.
- **All `translatableRoutes` related methods have been removed** from `LaravelLocalization`.
- **Removed `LaravelLocalizationRoutes` middleware** and its associated `$routeName` attribute.
- Removed `createUrlFromUri` method
- Removed huge `extractAttributes` method, no longer needed
- Translated routes (defined in `/lang/routes.php`) can no longer have a manual route name. Instead, each route gets a localized name per allowed locale.
  Routes outside of `/lang/routes.php` can still use manual names. To generate URLs for translated routes, you may use `LaralvelLocalisation::transRoute($key)` or tha alias helper `localized_trans_route($key)` with the corresponding key `$key` from `/lang/routes.php.`
- In case you use `hiddenDefaultLocale` there might be an addition redirect when using `redirect(route(...))`. This is caused, because the name of your route is always mapped to the route with the `{localize}` parameter.
  To avoid
- Removed side effect of `transRoute`, this no longer changes the current locale.
- The `currentLocale` variable of LaravelLocallization is now always identical to `App::getLocale()`. It may be removed.

If something crucial was removed by mistake or if you encounter missing functionality, feel free to **create an issue**.

## Test changes

### Changes in `testTranslatedRoutes` 
General: Instead of `route` helper we need to use `localized_trans_route` helper.

Old:

```php
$this->assertEquals(route('about'), 'http://localhost/about');
$this->assertEquals(route('about'), 'http://localhost/about');
```

New:

```php
$this->assertEquals('http://localhost/en/about', localized_trans_route('about'));
$this->assertEquals( 'http://localhost/en/about', localized_trans_route('about'));
```

Explanation:

1. Translated routes can no longer have manual names. Use the `localized_trans_route` helper instead.
2. The expected value should come first in assertEquals.
3. `hiddenDefaultLocale` is disabled, and the locale is set to `en`, so the URL must include the locale.
