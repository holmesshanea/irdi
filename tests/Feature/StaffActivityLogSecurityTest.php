<?php

use App\Models\StaffActivityLog;
use App\Models\User;
use App\Support\StaffActivityActions;
use Illuminate\Support\Facades\File;
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

test('staff activity action catalog contains every categorized action', function () {
    $categorizedActions = collect(StaffActivityActions::categories())
        ->flatten()
        ->unique()
        ->sort()
        ->values();

    $catalogActions = collect(StaffActivityActions::all())
        ->sort()
        ->values();

    expect($categorizedActions->all())
        ->toBe($catalogActions->all());
});

test('every recorded staff activity action exists in the action catalog', function () {
    $paths = [
        resource_path('views/components/admin'),
        app_path(),
    ];

    $recordedActions = collect();

    foreach ($paths as $path) {
        foreach (File::allFiles($path) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = File::get($file->getPathname());

            preg_match_all(
                "/StaffActivity::record\\(.*?action:\\s*['\"]([^'\"]+)['\"]/s",
                $contents,
                $matches,
            );

            $recordedActions->push(...$matches[1]);
        }
    }

    $recordedActions = $recordedActions
        ->unique()
        ->sort()
        ->values();

    $catalogActions = collect(StaffActivityActions::all())
        ->unique()
        ->sort()
        ->values();

    expect($recordedActions->all())
        ->not->toBeEmpty();

    expect(
        $recordedActions->diff($catalogActions)->values()->all()
    )->toBe([]);
});
