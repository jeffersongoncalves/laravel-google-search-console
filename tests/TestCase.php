<?php

namespace JeffersonGoncalves\GoogleSearchConsole\Tests;

use JeffersonGoncalves\GoogleSearchConsole\GoogleSearchConsoleServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            GoogleSearchConsoleServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('google-search-console.access_token', 'fake-access-token');
        $app['config']->set('google-search-console.site_url', 'https://example.com/');
        $app['config']->set('google-search-console.timeout', 5);
    }
}
