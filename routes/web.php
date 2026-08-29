<?php

use App\Http\Controllers\PropertyReviewInvitationController;
use App\Models\MemberProfile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Public Pages
|--------------------------------------------------------------------------
*/

Route::view('/', 'pages.home')
    ->name('home');

Route::view('/about', 'pages.about')
    ->name('about');

Route::view('/membership', 'pages.membership')
    ->name('membership');

Route::livewire('/directory', 'pages::directory')
    ->name('directory');

Route::view('/faq', 'pages.faq')
    ->name('faq');

Route::view('/contact', 'pages.contact')
    ->name('contact');

Route::view('/privacy-policy', 'pages.privacy-policy')
    ->name('privacy-policy');

Route::view('/terms', 'pages.terms')
    ->name('terms');

/*
|--------------------------------------------------------------------------
| Authenticated Member Pages
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Account
    |--------------------------------------------------------------------------
    */

    Route::view('/account', 'pages.account')
        ->name('account');

    Route::livewire('/account/delete', 'account.delete')
        ->name('account.delete');

    Route::livewire('/account/reviews', 'pages::account.reviews')
        ->name('account.reviews');

    /*
    |--------------------------------------------------------------------------
    | Membership
    |--------------------------------------------------------------------------
    */

    Route::livewire('/membership/join', 'membership.join')
        ->name('membership.join');

    Route::view('/code-of-ethics', 'pages.code-of-ethics')
        ->name('code-of-ethics');

    Route::view('/best-practices', 'pages.best-practices')
        ->name('best-practices');

    /*
    |--------------------------------------------------------------------------
    | Member Profiles
    |--------------------------------------------------------------------------
    */

    Route::livewire('/account/profiles/create', 'member-profiles.create')
        ->name('member-profiles.create');

    Route::livewire('/account/profiles/{profile}/edit', 'member-profiles.edit')
        ->name('account.profiles.edit');

    Route::delete('/account/profiles/{profile}', function (MemberProfile $profile) {
        if ($profile->user_id !== auth()->id()) {
            abort(403);
        }

        if ($profile->profile_image) {
            Storage::disk(config('filesystems.profile_images_disk', 'public'))
                ->delete($profile->profile_image);
        }

        $profile->delete();

        return redirect()
            ->route('account')
            ->with(
                'status',
                'Your profile has been deleted. Your IRDI account and membership were not affected.'
            );
    })->name('account.profiles.destroy');

    /*
    |--------------------------------------------------------------------------
    | Messages
    |--------------------------------------------------------------------------
    */

    Route::livewire(
        '/messages/{profile:username}/create',
        'messages.create'
    )->name('messages.create');

    /*
    |--------------------------------------------------------------------------
    | Property Review Invitations
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/account/profiles/{profile}/review-invitations',
        [PropertyReviewInvitationController::class, 'store']
    )->name('account.review-invitations.store');

    Route::post(
        '/account/review-invitations/{invitation}/resend',
        [PropertyReviewInvitationController::class, 'resend']
    )->name('account.review-invitations.resend');

    Route::post(
        '/account/review-invitations/{invitation}/cancel',
        [PropertyReviewInvitationController::class, 'cancel']
    )->name('account.review-invitations.cancel');
});

/*
|--------------------------------------------------------------------------
| Public Member Profiles
|--------------------------------------------------------------------------
*/

Route::livewire(
    '/directory/{profile:username}/card',
    'member-profiles.card'
)->name('member-profiles.card');

Route::livewire(
    '/directory/{profile:username}',
    'member-profiles.show'
)->name('member-profiles.show');

/*
|--------------------------------------------------------------------------
| Property Review Form
|--------------------------------------------------------------------------
*/

Route::livewire('/review/{token}', 'reviews.show')
    ->name('property-reviews.show');

/*
|--------------------------------------------------------------------------
| Administration
|--------------------------------------------------------------------------
*/

Route::livewire('/admin/reviews', 'admin.reviews.index')
    ->middleware(['auth', 'verified', 'admin'])
    ->name('admin.reviews.index');

Route::livewire('/admin/reviews/{review}', 'admin.reviews.show')
    ->middleware(['auth', 'verified'])
    ->name('admin.reviews.show');
