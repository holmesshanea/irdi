<?php

use App\Models\StaffActivityLog;
use App\Models\User;
use Livewire\Livewire;

test('staff activity log rejects a tampered pagination size', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.activity.index')
        ->set('perPage', 1000000)
        ->assertSet('perPage', 25);
});

test('membership activity category includes restored probation activity', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $target = User::factory()->create();

    StaffActivityLog::create([
        'actor_id' => $admin->id,
        'target_user_id' => $target->id,
        'action' => 'membership_probation_restored',
        'description' => 'Probation restored.',
        'ip_address' => '127.0.0.1',
    ]);

    StaffActivityLog::create([
        'actor_id' => $admin->id,
        'target_user_id' => $target->id,
        'action' => 'moderator_assigned',
        'description' => 'Moderator assigned.',
        'ip_address' => '127.0.0.1',
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.activity.index')
        ->set('category', 'membership')
        ->assertSee('Probation restored.')
        ->assertDontSee('Moderator assigned.');
});
