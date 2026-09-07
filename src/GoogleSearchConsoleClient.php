<?php

namespace JeffersonGoncalves\GoogleSearchConsole;

use DateTime;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * Google Search Console REST API HTTP layer. Wraps search analytics queries,
 * URL inspection, and sitemap management behind a small static client,
 * threading the OAuth bearer token, and returning null/[]/false sentinels on
 * ordinary HTTP failures instead of throwing.
 */
class GoogleSearchConsoleClient
{
    private const BASE_URL = 'https://searchconsole.googleapis.com';

    /**
     * @return list<array<string, mixed>>
     */
    public static function searchAnalyticsByQuery(?string $siteUrl = null, ?string $startDate = null, ?string $endDate = null, int $limit = 100): array
    {
        return self::searchAnalytics('query', $siteUrl, $startDate, $endDate, $limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function searchAnalyticsByPage(?string $siteUrl = null, ?string $startDate = null, ?string $endDate = null, int $limit = 100): array
    {
        return self::searchAnalytics('page', $siteUrl, $startDate, $endDate, $limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function searchAnalyticsByCountry(?string $siteUrl = null, ?string $startDate = null, ?string $endDate = null, int $limit = 100): array
    {
        return self::searchAnalytics('country', $siteUrl, $startDate, $endDate, $limit);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function inspectUrl(string $url, ?string $siteUrl = null): ?array
    {
        $siteUrl = self::resolveSiteUrl($siteUrl);

        $response = self::request('post', '/v1/urlInspection/index:inspect', 'google_search_console_inspect_url', [
            'inspectionUrl' => $url,
            'siteUrl' => $siteUrl,
        ]);

        return self::jsonOrNull($response);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function sitemaps(?string $siteUrl = null): array
    {
        $siteUrl = self::resolveSiteUrl($siteUrl);

        $response = self::request('get', '/webmasters/v3/sites/'.rawurlencode($siteUrl).'/sitemaps', 'google_search_console_sitemaps');

        $data = self::jsonOrNull($response);

        return $data['sitemap'] ?? [];
    }

    public static function submitSitemap(string $sitemapUrl, ?string $siteUrl = null): bool
    {
        $siteUrl = self::resolveSiteUrl($siteUrl);

        $response = self::request('put', '/webmasters/v3/sites/'.rawurlencode($siteUrl).'/sitemaps/'.rawurlencode($sitemapUrl), 'google_search_console_submit_sitemap');

        return $response !== null && $response->successful();
    }

    public static function deleteSitemap(string $sitemapUrl, ?string $siteUrl = null): bool
    {
        $siteUrl = self::resolveSiteUrl($siteUrl);

        $response = self::request('delete', '/webmasters/v3/sites/'.rawurlencode($siteUrl).'/sitemaps/'.rawurlencode($sitemapUrl), 'google_search_console_delete_sitemap');

        return $response !== null && $response->successful();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function searchAnalytics(string $dimension, ?string $siteUrl, ?string $startDate, ?string $endDate, int $limit): array
    {
        $siteUrl = self::resolveSiteUrl($siteUrl);
        $defaultRange = self::defaultDateRange();

        $response = self::request('post', '/webmasters/v3/sites/'.rawurlencode($siteUrl).'/searchAnalytics/query', 'google_search_console_search_analytics', [
            'startDate' => $startDate ?? $defaultRange['startDate'],
            'endDate' => $endDate ?? $defaultRange['endDate'],
            'rowLimit' => $limit,
            'dimensions' => [$dimension],
        ]);

        $data = self::jsonOrNull($response);

        return $data['rows'] ?? [];
    }

    /**
     * Search Console data typically lags 2-3 days, so the default window
     * ends 3 days ago and spans the 28 days before that.
     *
     * @return array{startDate: string, endDate: string}
     */
    private static function defaultDateRange(): array
    {
        $endDate = new DateTime('-3 days');
        $startDate = (clone $endDate)->modify('-28 days');

        return [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
        ];
    }

    /**
     * Shared request/response handling: attaches the bearer token, catches
     * transport failures, and logs them rather than throwing.
     *
     * @param  'get'|'post'|'put'|'delete'  $method
     * @param  array<string, mixed>  $body
     */
    private static function request(string $method, string $path, string $context, array $body = []): ?Response
    {
        $url = self::BASE_URL.$path;

        $headers = [];

        if ($token = self::accessToken()) {
            $headers['Authorization'] = "Bearer {$token}";
        }

        try {
            $request = Http::timeout(self::timeout())->withHeaders($headers);

            return match ($method) {
                'get' => $request->get($url),
                'post' => $request->post($url, $body),
                'put' => $request->put($url, $body),
                'delete' => $request->delete($url, $body),
            };
        } catch (Throwable $e) {
            self::logFailure($context, $url, $e);

            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function jsonOrNull(?Response $response): ?array
    {
        if ($response === null || ! $response->successful()) {
            return null;
        }

        $data = $response->json();

        return is_array($data) ? $data : null;
    }

    /**
     * Falls back to `config('google-search-console.site_url')` when no site
     * URL is passed explicitly.
     *
     * @throws InvalidArgumentException
     */
    private static function resolveSiteUrl(?string $siteUrl): string
    {
        $siteUrl = $siteUrl ?: self::defaultSiteUrl();

        if ($siteUrl === null || $siteUrl === '') {
            throw new InvalidArgumentException("No Google Search Console site URL provided. Pass one explicitly or set config('google-search-console.site_url').");
        }

        return $siteUrl;
    }

    private static function defaultSiteUrl(): ?string
    {
        $siteUrl = config('google-search-console.site_url');

        return is_string($siteUrl) && $siteUrl !== '' ? $siteUrl : null;
    }

    private static function logFailure(string $context, string $target, Throwable $e): void
    {
        Log::warning('GoogleSearchConsoleClient outbound fetch failed', [
            'context' => $context,
            'target' => $target,
            'exception' => $e::class,
            'message' => $e->getMessage(),
        ]);
    }

    private static function accessToken(): ?string
    {
        $token = config('google-search-console.access_token');

        return is_string($token) && $token !== '' ? $token : null;
    }

    private static function timeout(): int
    {
        return (int) config('google-search-console.timeout', 8);
    }
}
