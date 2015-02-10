<?php namespace Mcamara\LaravelLocalization;

use Locale;
use Illuminate\Http\Request;

class LanguageNegotiator {

    /**
     * @var String
     */
    private $defaultLocale;

    /**
     * @var Array
     */
    private $supportedLanguages;

    /**
     * @var Request
     */
    private $request;


    /**
     * @param string $defaultLocale
     * @param array $supportedLanguages
     * @param Request $request
     */
    function __construct( $defaultLocale, $supportedLanguages, Request $request )
    {
        $this->defaultLocale = $defaultLocale;
        $this->supportedLanguages = $supportedLanguages;
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
     * @return string  The negotiated language result or app.locale.
     */
    public function negotiateLanguage()
    {
        $matches = $this->getMatchesFromAcceptedLanguages();
        foreach ( $matches as $key => $q )
        {
            if ( !empty( $this->supportedLanguages[ $key ] ) )
            {
                return $key;
            }
        }
        // If any (i.e. "*") is acceptable, return the first supported format
        if ( isset( $matches[ '*' ] ) )
        {
            return array_shift($this->supportedLanguages);
        }

        if ( class_exists('Locale') && !empty( $_SERVER[ 'HTTP_ACCEPT_LANGUAGE' ] ) )
        {
            $http_accept_language = Locale::acceptFromHttp($_SERVER[ 'HTTP_ACCEPT_LANGUAGE' ]);

            if ( !empty( $this->supportedLanguages[ $http_accept_language ] ) )
            {
                return $http_accept_language;
            }
        }

        if ( $this->request->server('REMOTE_HOST') )
        {
            $remote_host = explode('.', $this->request->server('REMOTE_HOST'));
            $lang = strtolower(end($remote_host));

            if ( !empty( $this->supportedLanguages[ $lang ] ) )
            {
                return $lang;
            }
        }

        return $this->defaultLocale;
    }

    /**
     * Return all the accepted languages from the browser
     * @return array Matches from the header field Accept-Languages
     */
    private function getMatchesFromAcceptedLanguages()
    {
        $matches = [ ];

        if ( $acceptLanguages = $this->request->header('Accept-Language') )
        {
            $acceptLanguages = explode(',', $acceptLanguages);

            $generic_matches = [ ];
            foreach ( $acceptLanguages as $option )
            {
                $option = array_map('trim', explode(';', $option));
                $l = $option[ 0 ];
                if ( isset( $option[ 1 ] ) )
                {
                    $q = (float)str_replace('q=', '', $option[ 1 ]);
                } else
                {
                    $q = null;
                    // Assign default low weight for generic values
                    if ( $l == '*/*' )
                    {
                        $q = 0.01;
                    } elseif ( substr($l, -1) == '*' )
                    {
                        $q = 0.02;
                    }
                }
                // Unweighted values, get high weight by their position in the
                // list
                $q = isset( $q ) ? $q : 1000 - count($matches);
                $matches[ $l ] = $q;

                //If for some reason the Accept-Language header only sends language with country
                //we should make the language without country an accepted option, with a value
                //less than it's parent.
                $l_ops = explode('-', $l);
                array_pop($l_ops);
                while ( !empty( $l_ops ) )
                {
                    //The new generic option needs to be slightly less important than it's base
                    $q -= 0.001;
                    $op = implode('-', $l_ops);
                    if ( empty( $generic_matches[ $op ] ) || $generic_matches[ $op ] > $q )
                    {
                        $generic_matches[ $op ] = $q;
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