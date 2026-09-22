<?php

use App\Models\MemberProfile;
use App\Models\PropertyReview;
use App\Models\PropertyReviewInvitation;
use App\Models\User;
use App\Support\ProfilePrivacy;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

function privacyTestProfile(): MemberProfile
{
    $user = User::factory()->create([
        'membership_status' => 'active',
        'ethics_agreed_at' => now(),
        'best_practices_agreed_at' => now(),
        'member_since' => now()->toDateString(),
    ]);

    return MemberProfile::factory()->create([
        'user_id' => $user->id,
        'profile_name' => 'Private Person Unique',
        'username' => 'public-alias',
        'directory_visible' => true,
        'allow_member_messages' => true,
        'city' => 'SecretCityUnique',
        'state_province' => 'SecretProvinceUnique',
        'country_code' => 'US',
        'country' => 'United States',
        'bio' => 'Private biography unique.',
        'website' => 'https://private-unique.example',
        'profile_image' => null,
    ]);
}

test('privacy encrypts the photo removes its public object and restores it under a new URL', function () {
    Storage::fake('public');
    config(['filesystems.profile_images_disk' => 'public']);
    $profile = privacyTestProfile();
    $profile->update(['profile_image' => 'profile-images/original.png']);
    Storage::disk('public')->put('profile-images/original.png', 'original-image-bytes');
    $this->actingAs($profile->user);

    app(ProfilePrivacy::class)->set($profile, true);
    $profile->refresh();
    expect($profile->personal_information_hidden)->toBeTrue();
    expect($profile->profile_image)->toBeNull();
    Storage::disk('public')->assertMissing('profile-images/original.png');
    $archive = $profile->encrypted_profile_image;
    $encrypted = Storage::disk('public')->get($archive);
    expect($encrypted)->not->toContain('original-image-bytes');
    expect(Crypt::decryptString($encrypted))->toContain(base64_encode('original-image-bytes'));

    app(ProfilePrivacy::class)->set($profile, false);
    $profile->refresh();
    expect($profile->profile_image)->not->toBe('profile-images/original.png');
    expect(Storage::disk('public')->get($profile->profile_image))->toBe('original-image-bytes');
    Storage::disk('public')->assertMissing($archive);
});

test('privacy keeps ratings and membership but cancels unused review invitations', function () {
    $profile = privacyTestProfile();
    $invitation = PropertyReviewInvitation::create([
        'member_profile_id' => $profile->id,
        'token' => Str::random(64),
        'reviewer_email' => 'reviewer@example.com',
        'expires_at' => now()->addDays(30),
    ]);
    $used = PropertyReviewInvitation::create([
        'member_profile_id' => $profile->id,
        'token' => Str::random(64),
        'reviewer_email' => 'past-reviewer@example.com',
        'expires_at' => now()->addDays(30),
        'used_at' => now(),
    ]);
    $review = PropertyReview::create([
        'member_profile_id' => $profile->id,
        'property_review_invitation_id' => $used->id,
        'reviewer_email' => 'past-reviewer@example.com',
        'respect_for_property' => 1,
        'communication_courtesy' => 1,
        'care_of_property' => 1,
        'would_allow_return' => false,
        'comments' => 'Original evidence stays intact.',
    ]);
    $this->actingAs($profile->user);
    Livewire::test('account.privacy')->set('hidePersonalInformation', true)->call('savePrivacy')->assertHasNoErrors();
    expect($invitation->fresh()->cancelled_at)->not->toBeNull();
    expect($used->fresh()->cancelled_at)->toBeNull();
    $this->assertDatabaseHas('property_reviews', ['id' => $review->id, 'respect_for_property' => 1]);
    expect($profile->user->fresh()->membership_status)->toBe('active');
    $this->post(route('account.review-invitations.store', $profile), ['reviewer_email' => 'new@example.com'])->assertForbidden();
    app(ProfilePrivacy::class)->set($profile, false);
    expect($invitation->fresh()->cancelled_at)->not->toBeNull();
});

test('privacy hides personal details from public pages and preserves verification by alias', function () {
    $profile = privacyTestProfile();
    $this->actingAs($profile->user);
    app(ProfilePrivacy::class)->set($profile, true);
    auth()->logout();

    foreach ([route('directory'), route('member-profiles.show', $profile->username), route('member-profiles.card', $profile->username)] as $url) {
        $this->get($url)->assertOk()->assertSee('public-alias')
            ->assertDontSee('Private Person Unique')
            ->assertDontSee('SecretCityUnique')
            ->assertDontSee('SecretProvinceUnique')
            ->assertDontSee('Private biography unique.')
            ->assertDontSee('private-unique.example');
    }
    $profile->update(['directory_visible' => false]);
    $this->get(route('member-profiles.show', $profile->username))->assertOk();
    expect(MemberProfile::query()->publicDirectory()->whereKey($profile->id)->exists())->toBeTrue();
});

test('directory search and location filters cannot identify privacy enabled members', function () {
    $profile = privacyTestProfile();
    $this->actingAs($profile->user);
    app(ProfilePrivacy::class)->set($profile, true);
    auth()->logout();
    Livewire::test('pages::directory')->set('search', 'Private Person Unique')->assertDontSee('public-alias');
    Livewire::test('pages::directory')->set('search', 'public-alias')->assertSee('public-alias');
    Livewire::test('pages::directory')->set('city', 'SecretCityUnique')->assertDontSee('public-alias');
});

test('another user cannot change privacy or access an archived image', function () {
    $profile = privacyTestProfile();
    $this->actingAs(User::factory()->create());
    $this->get(route('account.profiles.private-image', $profile))->assertForbidden();
    expect(fn () => app(ProfilePrivacy::class)->set($profile, true))->toThrow(HttpException::class);
    expect($profile->fresh()->personal_information_hidden)->toBeFalse();
});

test('a stale message form cannot send after the recipient enables privacy', function () {
    $profile = privacyTestProfile();
    $sender = User::factory()->create(['membership_status' => 'active']);
    $this->actingAs($sender);
    $component = Livewire::test('messages.create', ['profile' => $profile]);
    $this->actingAs($profile->user);
    app(ProfilePrivacy::class)->set($profile, true);
    $this->actingAs($sender);
    $component->set('subject', 'Message subject')->set('body', 'Message body')->call('send')->assertNotFound();
    $this->assertDatabaseCount('messages', 0);
});

test('stale profile editing cannot publish a new photo after privacy is enabled', function () {
    Storage::fake('public');
    $profile = privacyTestProfile();
    $this->actingAs($profile->user);
    $component = Livewire::test('member-profiles.edit', ['profile' => $profile]);
    app(ProfilePrivacy::class)->set($profile, true);
    $component->set('profileImage', UploadedFile::fake()->image('photo.jpg'))->call('save')->assertHasErrors('profileImage');
    expect($profile->fresh()->profile_image)->toBeNull();
});

test('an unreadable image prevents a successful privacy update', function () {
    Storage::fake('public');
    config(['filesystems.profile_images_disk' => 'public']);
    $profile = privacyTestProfile();
    $profile->update(['profile_image' => 'profile-images/missing.png']);
    $this->actingAs($profile->user);
    Livewire::test('account.privacy')->set('hidePersonalInformation', true)->call('savePrivacy')->assertHasErrors('hidePersonalInformation');
    expect($profile->fresh()->personal_information_hidden)->toBeFalse();
});

test('private image is readable only by owner or authorized staff', function () {
    Storage::fake('public');
    config(['filesystems.profile_images_disk' => 'public']);
    $profile = privacyTestProfile();
    $bytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl6oQAAAABJRU5ErkJggg==');
    $profile->update(['profile_image' => 'profile-images/photo.png']);
    Storage::disk('public')->put('profile-images/photo.png', $bytes);
    $this->actingAs($profile->user);
    app(ProfilePrivacy::class)->set($profile, true);
    $url = route('account.profiles.private-image', $profile);
    $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png');
    $this->actingAs(User::factory()->create(['is_admin' => true]))->get($url)->assertOk();
    $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
});

test('private profiles retain the existing published negative rating summary', function () {
    $profile = privacyTestProfile();
    for ($i = 0; $i < 3; $i++) {
        $invitation = PropertyReviewInvitation::create([
            'member_profile_id' => $profile->id,
            'token' => Str::random(64),
            'reviewer_email' => 'reviewer'.$i.'@example.com',
            'expires_at' => now()->addDays(30),
            'used_at' => now(),
        ]);
        PropertyReview::create([
            'member_profile_id' => $profile->id,
            'property_review_invitation_id' => $invitation->id,
            'reviewer_email' => $invitation->reviewer_email,
            'respect_for_property' => 1,
            'communication_courtesy' => 1,
            'care_of_property' => 1,
            'would_allow_return' => false,
        ]);
    }
    $this->actingAs($profile->user);
    app(ProfilePrivacy::class)->set($profile, true);
    auth()->logout();
    $this->get(route('member-profiles.show', $profile->username))->assertOk()->assertSee('1.0')->assertSee('public-alias');
    $this->assertDatabaseCount('property_reviews', 3);
});
