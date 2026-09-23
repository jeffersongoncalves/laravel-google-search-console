<div class="filament-hidden">

![Laravel Google Search Console](https://raw.githubusercontent.com/jeffersongoncalves/laravel-google-search-console/main/banners/laravel-google-search-console.png)

</div>

# Laravel Google Search Console

[![Tests](https://github.com/jeffersongoncalves/laravel-google-search-console/actions/workflows/tests.yml/badge.svg)](https://github.com/jeffersongoncalves/laravel-google-search-console/actions/workflows/tests.yml)
[![PHPStan](https://github.com/jeffersongoncalves/laravel-google-search-console/actions/workflows/phpstan.yml/badge.svg)](https://github.com/jeffersongoncalves/laravel-google-search-console/actions/workflows/phpstan.yml)
[![Code Style](https://github.com/jeffersongoncalves/laravel-google-search-console/actions/workflows/fix-php-code-style-issues.yml/badge.svg)](https://github.com/jeffersongoncalves/laravel-google-search-console/actions/workflows/fix-php-code-style-issues.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/jeffersongoncalves/laravel-google-search-console.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-google-search-console)
[![Total Downloads](https://img.shields.io/packagist/dt/jeffersongoncalves/laravel-google-search-console.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-google-search-console)
[![License](https://img.shields.io/packagist/l/jeffersongoncalves/laravel-google-search-console.svg?style=flat-square)](LICENSE.md)

A lightweight Google Search Console API client for Laravel. It wraps search analytics queries, URL inspection, and sitemap management behind a small static client, threads your OAuth bearer token, and returns `null`/`[]`/`false` sentinels on ordinary HTTP failures instead of throwing.

## Features

- **`searchAnalyticsByQuery()`** — search analytics rows broken down by query
- **`searchAnalyticsByPage()`** — search analytics rows broken down by page
- **`searchAnalyticsByCountry()`** — search analytics rows broken down by country
- **`inspectUrl()`** — run the URL Inspection API against a given URL
- **`sitemaps()`** — list submitted sitemaps
- **`submitSitemap()`** — submit a sitemap
- **`deleteSitemap()`** — delete a submitted sitemap

## Installation

```bash
composer require jeffersongoncalves/laravel-google-search-console
```

Optionally publish the config file:

```bash
php artisan vendor:publish --tag="google-search-console-config"
```

## Configuration

Add to your `.env`:

```env
GOOGLE_SEARCH_CONSOLE_ACCESS_TOKEN=ya29.xxxxxxxxxxxxxxxxxxxx
GOOGLE_SEARCH_CONSOLE_SITE_URL=https://example.com/
GOOGLE_SEARCH_CONSOLE_TIMEOUT=8
```

`GOOGLE_SEARCH_CONSOLE_ACCESS_TOKEN` is the OAuth bearer token — see the [authorizing guide](https://developers.google.com/webmaster-tools/v1/how-tos/authorizing). `GOOGLE_SEARCH_CONSOLE_SITE_URL` is the default verified site requests target when no site URL is passed explicitly — use the exact format shown in Search Console, e.g. `https://example.com/` or `sc-domain:example.com`.

### Config Options

```php
// config/google-search-console.php
return [
    'access_token' => env('GOOGLE_SEARCH_CONSOLE_ACCESS_TOKEN'),
    'site_url' => env('GOOGLE_SEARCH_CONSOLE_SITE_URL'),
    'timeout' => (int) env('GOOGLE_SEARCH_CONSOLE_TIMEOUT', 8),
];
```

## Usage

```php
use JeffersonGoncalves\GoogleSearchConsole\GoogleSearchConsoleClient;

// Search analytics — site URL defaults to config('google-search-console.site_url'),
// dates default to a 28-day window ending 3 days ago (Search Console's usual data lag)
$byQuery = GoogleSearchConsoleClient::searchAnalyticsByQuery();
$byPage = GoogleSearchConsoleClient::searchAnalyticsByPage(startDate: '2026-01-01', endDate: '2026-01-31', limit: 50);
$byCountry = GoogleSearchConsoleClient::searchAnalyticsByCountry();

// URL inspection
$result = GoogleSearchConsoleClient::inspectUrl('https://example.com/page');

// Sitemaps
$sitemaps = GoogleSearchConsoleClient::sitemaps();
GoogleSearchConsoleClient::submitSitemap('https://example.com/sitemap.xml');
GoogleSearchConsoleClient::deleteSitemap('https://example.com/sitemap.xml');

// Target a different site than the configured default
$sitemaps = GoogleSearchConsoleClient::sitemaps(siteUrl: 'sc-domain:example.com');
```

## Testing

```bash
composer test
```

## Static Analysis

```bash
composer analyse
```

## Code Formatting

```bash
composer format
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
