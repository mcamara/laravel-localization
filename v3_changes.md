# Changes

The major architectural change, discussed in [#921](https://github.com/mcamara/laravel-localization/issues/921), shifts locale handling from the route file to middleware.

This allows us to remove the custom caching solution and rely on Laravel's default caching. However, it introduces a new workaround for translated routes—although they never worked completely bug-free in the first place.

Below is a list of functions and features removed in v3. Some code was removed due to a lack of documentation, tests, or a clear explanation in the pull request. Additionally, a lot of code appeared to be unused, and even after careful reverse engineering, its purpose remained unclear—so it was removed.

If you notice something critical missing, please **open an issue**.

## Removals & Changes

- **Removed custom caching command** – Now fully compatible with Laravel’s built-in caching.
- **Locale is now a route parameter** instead of being set directly in route definitions.
- **Removed `baseUrl` property and related methods**.
- **`getBaseUrl()` is no longer used** – If you have a valid use case, please open an issue.
- **Removed `route.translation` event** – Documentation was unclear, there were open issues, and it was inconsistently triggered (only when the URL was empty during localization).
- **The `data` attribute is no longer removed from routes attributes** – This should not be the responsibility of the package.
- **`getLocalizedURL(locale: false)` no longer removes the locale from the URL**.
- **Dropped alias `localizeURL`** – If needed, you can define a custom helper.
- **`translatedRoutes` is no longer stored inside `LaravelLocalization`**.
- **`transRoute()` method is no longer supported** – Use `__('routes.*')` instead.
- **Removed `getNonLocalizedURL()`**.
- **All `translatableRoutes` related methods have been removed** from `LaravelLocalization`.
- **Removed `LaravelLocalizationRoutes` middleware** and its associated `$routeName` attribute.

If something crucial was removed by mistake or if you encounter missing functionality, feel free to **create an issue**.

