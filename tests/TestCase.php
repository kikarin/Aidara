<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Setup test environment.
     * Disable reCAPTCHA validation for testing.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable reCAPTCHA validation in testing environment
        config(['services.recaptcha.secret_key' => null]);
        config(['services.recaptcha.site_key' => null]);
        
        // Disable CSRF token validation for all tests
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }
}
