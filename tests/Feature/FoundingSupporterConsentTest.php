<?php

use App\Models\FoundingSupporter;
use App\Models\User;

test('public recognition requires an auditable consent timestamp', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin)->post(route('admin.founding-supporters.store'), [
        'display_name' => 'Opted In', 'tier' => 'founder', 'is_public' => '1',
    ])->assertRedirect();
    $supporter = FoundingSupporter::where('display_name', 'Opted In')->firstOrFail();
    expect($supporter->recognition_consented_at)->not->toBeNull();
    $this->get(route('founding-supporters'))->assertSee('Opted In');

    $this->put(route('admin.founding-supporters.update', $supporter), [
        'display_name' => 'Opted In', 'tier' => 'founder', 'is_public' => '0',
    ])->assertRedirect();
    $supporter->refresh();
    expect($supporter->recognition_consented_at)->toBeNull();
    $this->get(route('founding-supporters'))->assertDontSee('Opted In');
});

test('older public supporter entries stay hidden until consent is confirmed', function () {
    $supporter = FoundingSupporter::create([
        'display_name' => 'Unconfirmed Legacy Supporter', 'tier' => 'launch', 'is_public' => true,
    ]);
    $this->get(route('founding-supporters'))->assertDontSee('Unconfirmed Legacy Supporter');
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin)->put(route('admin.founding-supporters.update', $supporter), [
        'display_name' => 'Unconfirmed Legacy Supporter', 'tier' => 'launch', 'is_public' => '1',
    ])->assertRedirect();
    $this->get(route('founding-supporters'))->assertSee('Unconfirmed Legacy Supporter');
});

test('public navigation exposes founding supporters page', function () {
    $this->get(route('home'))->assertOk()->assertSee(route('founding-supporters'));
});
