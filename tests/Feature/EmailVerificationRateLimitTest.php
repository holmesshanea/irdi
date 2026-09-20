<?php

use App\Models\User;

test('email verification resend is rate limited', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user);

    for ($attempt = 1; $attempt <= 6; $attempt++) {
        $this->post(route('verification.send'))
            ->assertRedirect();
    }

    $this->post(route('verification.send'))
        ->assertTooManyRequests();
});
