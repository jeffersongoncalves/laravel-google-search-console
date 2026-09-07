<?php

namespace JeffersonGoncalves\GoogleSearchConsole;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class GoogleSearchConsoleServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('google-search-console')
            ->hasConfigFile();
    }
}
