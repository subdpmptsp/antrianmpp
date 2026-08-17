<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_browser_security_headers_are_added_to_responses(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader(
                'Permissions-Policy',
                'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
            )
            ->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_hsts_is_only_added_to_https_responses(): void
    {
        $this->get('https://localhost/admin/login')
            ->assertOk()
            ->assertHeader(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains',
            );
    }
}
