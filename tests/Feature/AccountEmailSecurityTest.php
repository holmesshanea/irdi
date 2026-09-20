
<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

test('account email change requires the current password', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'member@example.com',
        'email_verified_at' => now(),
        'password' => Hash::make('current-password'),
    ]);

    $verifiedAt = $user->email_verified_at?->toDateTimeString();

    $this->actingAs($user);

    Livewire::test('account.change-email')
        ->set('email', 'new-email@example.com')
        ->set('currentPassword', 'wrong-password')
        ->call('updateEmail')
        ->assertHasErrors(['currentPassword']);

    $user->refresh();

    expect($user->email)->toBe('member@example.com')
        ->and($user->email_verified_at?->toDateTimeString())->toBe($verifiedAt);
});

test('account email change succeeds with the correct current password', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'member@example.com',
        'email_verified_at' => now(),
        'password' => Hash::make('current-password'),
    ]);

    $this->actingAs($user);

    Livewire::test('account.change-email')
        ->set('email', 'new-email@example.com')
        ->set('currentPassword', 'current-password')
        ->call('updateEmail')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->email)->toBe('new-email@example.com')
        ->and($user->email_verified_at)->toBeNull();
});
