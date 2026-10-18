<?php

use App\Models\FoundingSupporter;
use App\Models\User;

it('restricts supporter administration to verified administrators', function () {
    $member = User::factory()->create();
    $moderator = User::factory()->create(['is_moderator' => true]);
    $admin = User::factory()->create(['is_admin' => true]);

    $this->get(route('admin.founding-supporters.index'))->assertRedirect();
    $this->actingAs($member)->get(route('admin.founding-supporters.index'))->assertForbidden();
    $this->actingAs($moderator)->get(route('admin.founding-supporters.index'))->assertForbidden();
    $this->actingAs($admin)->get(route('admin.founding-supporters.index'))->assertOk();
});

it('keeps new supporters private until explicit opt in', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin)->post(route('admin.founding-supporters.store'), [
        'display_name' => 'Private Launch Backer', 'tier' => 'launch', 'is_public' => '0',
    ])->assertRedirect(route('admin.founding-supporters.index'));
    $supporter = FoundingSupporter::where('display_name', 'Private Launch Backer')->firstOrFail();
    expect($supporter->is_public)->toBeFalse();
    $this->get(route('founding-supporters'))->assertDontSee('Private Launch Backer');
    $this->put(route('admin.founding-supporters.update', $supporter), [
        'display_name' => 'Public Launch Backer', 'tier' => 'launch', 'is_public' => '1',
    ])->assertRedirect();
    $this->get(route('founding-supporters'))->assertSee('Public Launch Backer');
});

it('validates tiers and prevents duplicate backer identifiers', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    FoundingSupporter::create(['display_name' => 'Existing', 'tier' => 'founder', 'is_public' => false, 'kickstarter_backer_id' => 'backer-123']);
    $this->actingAs($admin)->post(route('admin.founding-supporters.store'), [
        'display_name' => 'Invalid', 'tier' => 'invalid', 'is_public' => '1',
    ])->assertSessionHasErrors('tier');
    $this->post(route('admin.founding-supporters.store'), [
        'display_name' => 'Duplicate', 'tier' => 'launch', 'is_public' => '0', 'kickstarter_backer_id' => 'backer-123',
    ])->assertSessionHasErrors('kickstarter_backer_id');
});

it('prevents non administrators from changing or removing supporters', function () {
    $supporter = FoundingSupporter::create(['display_name' => 'Protected', 'tier' => 'charter', 'is_public' => true]);
    $member = User::factory()->create();
    $this->actingAs($member)->put(route('admin.founding-supporters.update', $supporter), [
        'display_name' => 'Tampered', 'tier' => 'founder', 'is_public' => '0',
    ])->assertForbidden();
    $this->delete(route('admin.founding-supporters.destroy', $supporter))->assertForbidden();
    $this->assertDatabaseHas('founding_supporters', ['id' => $supporter->id, 'display_name' => 'Protected']);
});
