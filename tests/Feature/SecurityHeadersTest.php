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
