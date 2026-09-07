<?php

use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\GoogleSearchConsole\Tests\TestCase;

uses(TestCase::class)
    ->beforeEach(fn () => Http::preventStrayRequests())
    ->in('Feature');
