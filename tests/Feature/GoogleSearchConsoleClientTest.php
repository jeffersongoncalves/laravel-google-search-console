<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\GoogleSearchConsole\GoogleSearchConsoleClient;

it('fetches search analytics by query', function () {
    Http::fake([
        'searchconsole.googleapis.com/*' => Http::response([
            'rows' => [['keys' => ['laravel package'], 'clicks' => 10]],
        ], 200),
    ]);

    $rows = GoogleSearchConsoleClient::searchAnalyticsByQuery();

    expect($rows)->toBe([['keys' => ['laravel package'], 'clicks' => 10]]);

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://searchconsole.googleapis.com/webmasters/v3/sites/https%3A%2F%2Fexample.com%2F/searchAnalytics/query'
            && $request->hasHeader('Authorization', 'Bearer fake-access-token')
            && $request['dimensions'] === ['query'];
    });
});

it('returns an empty array when search analytics by query fails', function () {
    Http::fake([
        'searchconsole.googleapis.com/*' => Http::response('', 403),
    ]);

    expect(GoogleSearchConsoleClient::searchAnalyticsByQuery())->toBe([]);
});

it('fetches search analytics by page', function () {
    Http::fake([
        'searchconsole.googleapis.com/*' => Http::response([
            'rows' => [['keys' => ['https://example.com/page'], 'clicks' => 5]],
        ], 200),
    ]);

    expect(GoogleSearchConsoleClient::searchAnalyticsByPage())->toBe([['keys' => ['https://example.com/page'], 'clicks' => 5]]);

    Http::assertSent(fn (Request $request) => $request['dimensions'] === ['page']);
});

it('returns an empty array when search analytics by page fails', function () {
    Http::fake([
        'searchconsole.googleapis.com/*' => Http::response('', 500),
    ]);

    expect(GoogleSearchConsoleClient::searchAnalyticsByPage())->toBe([]);
});

it('fetches search analytics by country', function () {
    Http::fake([
        'searchconsole.googleapis.com/*' => Http::response([
            'rows' => [['keys' => ['bra'], 'clicks' => 3]],
        ], 200),
    ]);

    expect(GoogleSearchConsoleClient::searchAnalyticsByCountry())->toBe([['keys' => ['bra'], 'clicks' => 3]]);

    Http::assertSent(fn (Request $request) => $request['dimensions'] === ['country']);
});

it('returns an empty array when search analytics by country fails', function () {
    Http::fake([
        'searchconsole.googleapis.com/*' => Http::response('', 500),
    ]);

    expect(GoogleSearchConsoleClient::searchAnalyticsByCountry())->toBe([]);
});

it('defaults the search analytics date range to a 28-day window ending 3 days ago', function () {
    Http::fake([
        'searchconsole.googleapis.com/*' => Http::response(['rows' => []], 200),
    ]);

    GoogleSearchConsoleClient::searchAnalyticsByQuery();

    $expectedEnd = (new DateTime('-3 days'))->format('Y-m-d');
    $expectedStart = (new DateTime('-3 days'))->modify('-28 days')->format('Y-m-d');

    Http::assertSent(fn (Request $request) => $request['startDate'] === $expectedStart && $request['endDate'] === $expectedEnd);
});

it('accepts explicit dates and row limit for search analytics', function () {
    Http::fake([
        'searchconsole.googleapis.com/*' => Http::response(['rows' => []], 200),
    ]);

    GoogleSearchConsoleClient::searchAnalyticsByQuery(startDate: '2026-01-01', endDate: '2026-01-31', limit: 10);

    Http::assertSent(function (Request $request) {
        return $request['startDate'] === '2026-01-01'
            && $request['endDate'] === '2026-01-31'
            && $request['rowLimit'] === 10;
    });
});

it('inspects a URL', function () {
    Http::fake([
        'searchconsole.googleapis.com/*' => Http::response([
            'inspectionResult' => ['indexStatusResult' => ['verdict' => 'PASS']],
        ], 200),
    ]);

    $result = GoogleSearchConsoleClient::inspectUrl('https://example.com/page');

    expect($result)->toBe(['inspectionResult' => ['indexStatusResult' => ['verdict' => 'PASS']]]);

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://searchconsole.googleapis.com/v1/urlInspection/index:inspect'
            && $request['inspectionUrl'] === 'https://example.com/page'
            && $request['siteUrl'] === 'https://example.com/';
    });
});

it('returns null when URL inspection fails', function () {
    Http::fake([
        'searchconsole.googleapis.com/*' => Http::response('', 403),
    ]);

    expect(GoogleSearchConsoleClient::inspectUrl('https://example.com/page'))->toBeNull();
});

it('lists sitemaps', function () {
    Http::fake([
        'searchconsole.googleapis.com/*' => Http::response([
            'sitemap' => [['path' => 'https://example.com/sitemap.xml']],
        ], 200),
    ]);

    expect(GoogleSearchConsoleClient::sitemaps())->toBe([['path' => 'https://example.com/sitemap.xml']]);

    Http::assertSent(fn (Request $request) => $request->url() === 'https://searchconsole.googleapis.com/webmasters/v3/sites/https%3A%2F%2Fexample.com%2F/sitemaps');
});

it('returns an empty array when listing sitemaps fails', function () {
    Http::fake([
        'searchconsole.googleapis.com/*' => Http::response('', 403),
    ]);

    expect(GoogleSearchConsoleClient::sitemaps())->toBe([]);
});

it('submits a sitemap', function () {
    Http::fake([
        'searchconsole.googleapis.com/*' => Http::response('', 200),
    ]);

    expect(GoogleSearchConsoleClient::submitSitemap('https://example.com/sitemap.xml'))->toBeTrue();

    Http::assertSent(function (Request $request) {
        return $request->method() === 'PUT'
            && $request->url() === 'https://searchconsole.googleapis.com/webmasters/v3/sites/https%3A%2F%2Fexample.com%2F/sitemaps/https%3A%2F%2Fexample.com%2Fsitemap.xml';
    });
});

it('returns false when submitting a sitemap fails', function () {
    Http::fake([
        'searchconsole.googleapis.com/*' => Http::response('', 403),
    ]);

    expect(GoogleSearchConsoleClient::submitSitemap('https://example.com/sitemap.xml'))->toBeFalse();
});

it('deletes a sitemap', function () {
    Http::fake([
        'searchconsole.googleapis.com/*' => Http::response('', 204),
    ]);

    expect(GoogleSearchConsoleClient::deleteSitemap('https://example.com/sitemap.xml'))->toBeTrue();

    Http::assertSent(fn (Request $request) => $request->method() === 'DELETE');
});

it('returns false when deleting a sitemap fails', function () {
    Http::fake([
        'searchconsole.googleapis.com/*' => Http::response('', 403),
    ]);

    expect(GoogleSearchConsoleClient::deleteSitemap('https://example.com/sitemap.xml'))->toBeFalse();
});

it('uses an explicit site URL over the configured default', function () {
    Http::fake([
        'searchconsole.googleapis.com/*' => Http::response(['sitemap' => []], 200),
    ]);

    GoogleSearchConsoleClient::sitemaps('sc-domain:other.com');

    Http::assertSent(fn (Request $request) => $request->url() === 'https://searchconsole.googleapis.com/webmasters/v3/sites/sc-domain%3Aother.com/sitemaps');
});

it('rejects a missing site URL', function () {
    config()->set('google-search-console.site_url', null);

    expect(fn () => GoogleSearchConsoleClient::sitemaps())
        ->toThrow(InvalidArgumentException::class);
});
