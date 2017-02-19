### 1.2.3
- Added `getLocalesOrder()` function to the package

### 1.2
- Added compatibility with Laravel 5.4

### 1.1
- Added compatibility with Laravel 5.2

### 1.0.12
- Added regional for date localization

### 1.0.7
- Added Session and Cookie Middleware
- Deleted useSessionLocale and useCookieLocale from config file

### 1.0
- Laravel 5 supported
- Added Middleware
- Removed deprecated functions

### 0.15.0
- Added tests from scratch
- Refactored multiple functions
- getLocalizedURL now accepts attributes for the url (if needed)
- $routeName is always a string, no need to be an array if it just have the translation key for the current url

### 0.14.0
- Laravel 4.2 compatibility
- Removed Laravel 4.0 compatibility

### 0.13.5
- Fixes issue with grouped routes

### 0.13.4
- Fixes issue localizing a url when segment starts with a locale

### 0.13.3
- Allow no url to be passed in localizeURL

### 0.13.2
- Fixes issue with double slashes in localized urls
- Strip trailing slashes from all localized urls

### 0.13.1
- Fixes URL localization issue when the base path is not / (a.k.a, Laravel is not installed in the web root).

### 0.13.0
- Deprecated "getLanguageBar"

### 0.12.1
- Throws exception if Larvel's default locale is not in the array of supported locales.

### 0.12.0
- Changes 302 redirect in to 307 to prevent POST values from being consumed.
- Added localizeURL function

### 0.11.0
- Deprecated "getCurrentLanguageDirection", "getCurrentLanguageScript"
- Added "getCurrentLocaleDirection", "getCurrentLocaleScript", "getCurrentLocaleName"

### 0.10.1
- Fixes to maintain compatibility with older config and languagebar.blade.php templates
- Fixed backward compatibility of getLanguageBar
- getLocalizedURL now returns URL paths in the same format as parameter inputted; trailing and leading slashes or lack of are respected.
- getLocalizedURL now compatible with querystrings
- merged getNonLocalizedURL and getLocalizedURL
- getNonLocalizedURL($url = null) is now a wrapper for getLocalizedURL(false, $url = null)

### 0.10
- Standardizing function names and variables using locale
- Deprecated getCleanRoute
- Deprecated useBrowserLanguage
- Changed config useBrowserLanguage to useAcceptLanguageHeader
- Deprecated useSessionLanguage
- Changed config useSessionLanguage to useSessionLocale
- Deprecated useCookieLanguagee
- Changed config useCookieLanguage to useCookieLocale

### 0.9
- Fixes issue #47
- Fixes issue where getCleanRoute would only clean out the currently set locale.
- getLocalizedURL now throws an UnsupportedLocaleException if the requested locale is not in the list of supported locales.

### 0.8
- Changed getLanguageBar to just return view.  All other code has been moved to languagebar view.
- Deprecated getPrintCurrentLanguage
- Deprecated getLanguageBarClassName

### 0.7
- Merged languagesAllowed & supportedLanguages
- Added native for language names
- Added new function getSupportedLocales
- Deprecated getAllowedLanguages use getSupportedLocales instead
- Deprecated getSupportedLanguages use getSupportedLocales instead

### 0.6
- Added support for language script and direction

### 0.5
- Added multi-language routes
- Function `getCurrentLanguage` is not static

### 0.4
- Added the ability to edit the language bar code

### 0.3
- Added 'LaravelLocalizationRedirectFilter' filter

### 0.2
- Added `getURLLanguage` method.
- Added `getLanguageBar` method.
- Added `getURLLanguage` method.
- Added config file
- Added `useBrowserLanguage` config value
- Added README

### 0.1
 - Initial release.
