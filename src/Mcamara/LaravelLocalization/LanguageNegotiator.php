<?php

namespace Mcamara\LaravelLocalization;

use Illuminate\Http\Request;
use Locale;

class LanguageNegotiator
{
    /**
     * @var string
     */
    private $defaultLocale;

    /**
     * @var array
     */
    private $supportedLanguages;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var bool
     */
    private $use_intl = false;

    /**
     * @param string  $defaultLocale
     * @param array   $supportedLanguages
     * @param Request $request
     */
    public function __construct($defaultLocale, $supportedLanguages, Request $request)
    {
        $this->defaultLocale = $defaultLocale;

        if (class_exists('Locale')) {
            $this->use_intl = true;

            foreach ($supportedLanguages as $key => $supportedLanguage) {
                if ( ! isset($supportedLanguage['lang'])) {
                    $supportedLanguage['lang'] = Locale::canonicalize($key);
                } else {
                    $supportedLanguage['lang'] = Locale::canonicalize($supportedLanguage['lang']);
                }
                if (isset($supportedLanguage['regional'])) {
                    $supportedLanguage['regional'] = Locale::canonicalize($supportedLanguage['regional']);
                }

                $this->supportedLanguages[$key] = $supportedLanguage;
            }
        } else {
            $this->supportedLanguages = $supportedLanguages;
        }

        $this->request = $request;
    }

    /**
     * Negotiates language with the user's browser through the Accept-Language
     * HTTP header or the user's host address.  Language codes are generally in
     * the form "ll" for a language spoken in only one country, or "ll-CC" for a
     * language spoken in a particular country.  For example, U.S. English is
     * "en-US", while British English is "en-UK".  Portuguese as spoken in
     * Portugal is "pt-PT", while Brazilian Portuguese is "pt-BR".
     *
     * This function is based on negotiateLanguage from Pear HTTP2
     * http://pear.php.net/package/HTTP2/
     *
     * Quality factors in the Accept-Language: header are supported, e.g.:
     *      Accept-Language: en-UK;q=0.7, en-US;q=0.6, no, dk;q=0.8
     *
     * @return string The negotiated language result or app.locale.
     */
    public function negotiateLanguage()
    {
        $matches = $this->getMatchesFromAcceptedLanguages();
        foreach ($matches as $key => $q) {
            if (!empty($this->supportedLanguages[$key])) {
                return $key;
            }

            if ($this->use_intl) {
                $key = Locale::canonicalize($key);
            }

            // Search for acceptable locale by 'regional' => 'af_ZA' or 'lang' => 'af-ZA' match.
            foreach ( $this->supportedLanguages as $key_supported => $locale ) {
                if ( (isset($locale['regional']) && $locale['regional'] == $key) || (isset($locale['lang']) && $locale['lang'] == $key) ) {
                    return $key_supported;
                }
            }
        }
        // If any (i.e. "*") is acceptable, return the first supported format
        if (isset($matches['*'])) {
            reset($this->supportedLanguages);

            return key($this->supportedLanguages);
        }

        if ($this->use_intl && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $http_accept_language = Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']);

            if (!empty($this->supportedLanguages[$http_accept_language])) {
                return $http_accept_language;
            }
        }

        if ($this->request->server('REMOTE_HOST')) {
            $remote_host = explode('.', $this->request->server('REMOTE_HOST'));
            $lang = strtolower(end($remote_host));

            if (!empty($this->supportedLanguages[$lang])) {
                return $lang;
            }
        }

        return $this->defaultLocale;
    }

    /**
     * Return all the accepted languages from the browser.
     *
     * @return array Matches from the header field Accept-Languages
     */
    private function getMatchesFromAcceptedLanguages()
    {
        $matches = [];

        if ($acceptLanguages = $this->request->header('Accept-Language')) {
            $acceptLanguages = explode(',', $acceptLanguages);

            $generic_matches = [];
            foreach ($acceptLanguages as $option) {
                $option = array_map('trim', explode(';', $option));
                $l = $option[0];
                if (isset($option[1])) {
                    $q = (float) str_replace('q=', '', $option[1]);
                } else {
                    $q = null;
                    // Assign default low weight for generic values
                    if ($l == '*/*') {
                        $q = 0.01;
                    } elseif (substr($l, -1) == '*') {
                        $q = 0.02;
                    }
                }
                // Unweighted values, get high weight by their position in the
                // list
                $q = $q ?? 1000 - \count($matches);
                $matches[$l] = $q;

                //If for some reason the Accept-Language header only sends language with country
                //we should make the language without country an accepted option, with a value
                //less than it's parent.
                $l_ops = explode('-', $l);
                array_pop($l_ops);
                while (!empty($l_ops)) {
                    //The new generic option needs to be slightly less important than it's base
                    $q -= 0.001;
                    $op = implode('-', $l_ops);
                    if (empty($generic_matches[$op]) || $generic_matches[$op] > $q) {
                        $generic_matches[$op] = $q;
                    }
                    array_pop($l_ops);
                }
            }
            $matches = array_merge($generic_matches, $matches);

            arsort($matches, SORT_NUMERIC);
        }

        return $matches;
    }
}
