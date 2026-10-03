<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Uploaded media never touches the real storage folder during tests.
        Storage::fake('public');
        Storage::fake('local');

        // The storefront's fetch() calls send cookies (credentials: 'same-origin'); JSON test requests must too.
        $this->withCredentials();
    }

    /**
     * Each test request starts with fresh per-request services (the visitor's cart, wishlist),
     * as every real request does; otherwise state from an earlier request in the same test leaks in.
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null): TestResponse
    {
        $this->app->forgetScopedInstances();

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }
}
