<?php

test('web responses include baseline security headers', function () {
    $response = $this->get(route('home'));

    $response
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=()'
        );
});

test('web responses include report only content security policy', function () {
    $response = $this->get(route('home'));

    $response
        ->assertOk()
        ->assertHeader(
            'Content-Security-Policy-Report-Only',
            "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline' https://fonts.bunny.net; font-src 'self' https://fonts.bunny.net data:; img-src 'self' data: blob:; connect-src 'self' ws: wss:; frame-src 'self'; media-src 'self'; worker-src 'self' blob:"
        );
});

test('web responses prevent cross origin framing', function () {
    $response = $this->get(route('home'));

    $response
        ->assertOk()
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
});
