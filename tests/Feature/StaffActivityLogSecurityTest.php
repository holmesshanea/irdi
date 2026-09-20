<?php

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
