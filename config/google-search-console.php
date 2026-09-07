<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OAuth Access Token
    |--------------------------------------------------------------------------
    |
    | The OAuth bearer token used to authenticate requests to the Google
    | Search Console API. See:
    | https://developers.google.com/webmaster-tools/v1/how-tos/authorizing
    |
    */
    'access_token' => env('GOOGLE_SEARCH_CONSOLE_ACCESS_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Site URL
    |--------------------------------------------------------------------------
    |
    | The default verified site URL (e.g. `https://example.com/` or
    | `sc-domain:example.com`) requests are sent against when no site URL is
    | explicitly passed to the client.
    |
    */
    'site_url' => env('GOOGLE_SEARCH_CONSOLE_SITE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The number of seconds to wait for a response before giving up.
    |
    */
    'timeout' => (int) env('GOOGLE_SEARCH_CONSOLE_TIMEOUT', 8),
];
