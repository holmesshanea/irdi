<?php

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Notification;

test('guest can view the registration page', function () {
    $response = $this->get('/register');

    $response->assertOk();
});

test('guest can create an account', function () {
    $response = $this->post('/register', [
        'name' => 'Test Member',
        'email' => 'test-member@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertSessionHasNoErrors();

    $user = User::where('email', 'test-member@example.com')->first();

    expect($user)
        ->not->toBeNull()
        ->and($user->name)->toBe('Test Member');
});

test('new account starts with an unverified email address', function () {
    $this->post('/register', [
        'name' => 'Test Member',
        'email' => 'unverified@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertSessionHasNoErrors();

    $user = User::where('email', 'unverified@example.com')->firstOrFail();

    expect($user->email_verified_at)->toBeNull();
});

test('new account receives an email verification notification', function () {
    Notification::fake();

    $this->post('/register', [
        'name' => 'Test Member',
        'email' => 'verification@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertSessionHasNoErrors();

    $user = User::where('email', 'verification@example.com')->firstOrFail();

    Notification::assertSentTo(
        $user,
        VerifyEmailNotification::class,
    );
});

test('duplicate email address cannot register', function () {
    User::factory()->create([
        'email' => 'existing@example.com',
    ]);

    $response = $this->post('/register', [
        'name' => 'Another Member',
        'email' => 'existing@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertSessionHasErrors('email');

    expect(
        User::where('email', 'existing@example.com')->count()
    )->toBe(1);
});

test('registered user can log in with valid credentials', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect('/account');

    $this->assertAuthenticatedAs($user);
});

test('user cannot log in with an invalid password', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors();

    $this->assertGuest();
});

test('authenticated user can log out', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post('/logout');

    $response->assertRedirect('/');

    $this->assertGuest();
});

test('unverified user is directed to the email verification notice', function () {
    $user = User::factory()->unverified()->create();

    $response = $this
        ->actingAs($user)
        ->get('/email/verify');

    $response->assertOk();
});

test('user can verify their email address', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ],
    );

    $response = $this
        ->actingAs($user)
        ->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();

    $response->assertRedirect('/membership/join');
});

test('email verification fails with an invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        [
            'id' => $user->getKey(),
            'hash' => sha1('wrong-email@example.com'),
        ],
    );

    $this
        ->actingAs($user)
        ->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('user can request a password reset link', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->post('/forgot-password', [
        'email' => $user->email,
    ]);

    $response->assertSessionHasNoErrors();

    Notification::assertSentTo(
        $user,
        ResetPasswordNotification::class,
    );
});

test('user can reset their password with a valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', [
        'email' => $user->email,
    ]);

    $token = null;

    Notification::assertSentTo(
        $user,
        ResetPasswordNotification::class,
        function (ResetPasswordNotification $notification) use (&$token) {
            $token = $notification->token;

            return true;
        },
    );

    expect($token)->not->toBeNull();

    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'New-Secure-Password1',
        'password_confirmation' => 'New-Secure-Password1',
    ]);

    $response->assertSessionHasNoErrors();

    $this->assertTrue(
        Hash::check('New-Secure-Password1', $user->fresh()->password)
    );
});

test('user cannot reset their password with an invalid token', function () {
    $user = User::factory()->create();

    $originalPassword = $user->password;

    $response = $this->post('/reset-password', [
        'token' => 'invalid-reset-token',
        'email' => $user->email,
        'password' => 'New-Secure-Password1',
        'password_confirmation' => 'New-Secure-Password1',
    ]);

    $response->assertSessionHasErrors('email');

    expect($user->fresh()->password)->toBe($originalPassword);

    $this->assertFalse(
        Hash::check('New-Secure-Password1', $user->fresh()->password)
    );
});

test('guest cannot access the account page', function () {
    $response = $this->get('/account');

    $response->assertRedirect('/login');

    $this->assertGuest();
});

test('account cannot be created with a weak password', function () {
    $response = $this->post('/register', [
        'name' => 'Weak Password User',
        'email' => 'weak-password@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('password');

    $this->assertDatabaseMissing('users', [
        'email' => 'weak-password@example.com',
    ]);

    $this->assertGuest();
});

test('password reset token cannot be reused', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', [
        'email' => $user->email,
    ])->assertSessionHasNoErrors();

    $token = null;

    Notification::assertSentTo(
        $user,
        ResetPasswordNotification::class,
        function (ResetPasswordNotification $notification) use (&$token) {
            $token = $notification->token;

            return true;
        }
    );

    expect($token)->not->toBeNull();

    $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'First-Secure-Password1',
        'password_confirmation' => 'First-Secure-Password1',
    ])->assertSessionHasNoErrors();

    expect(
        Hash::check('First-Secure-Password1', $user->fresh()->password)
    )->toBeTrue();

    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'Second-Secure-Password1',
        'password_confirmation' => 'Second-Secure-Password1',
    ]);

    $response->assertSessionHasErrors('email');

    expect(
        Hash::check('First-Secure-Password1', $user->fresh()->password)
    )->toBeTrue();

    expect(
        Hash::check('Second-Secure-Password1', $user->fresh()->password)
    )->toBeFalse();
});

test('login is rate limited after repeated failed attempts', function () {
    $user = User::factory()->create([
        'email' => 'throttle@example.com',
        'password' => bcrypt('Correct-Password1'),
    ]);

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Wrong-Password1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'Correct-Password1',
    ]);

    $response->assertStatus(429);

    $this->assertGuest();
});
