<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Reset rate-limiter counters (stored in the cache) so throttled
        // endpoints (login / otp-request / public-search) start fresh per test.
        Cache::store(config('cache.default'))->clear();
    }
}
