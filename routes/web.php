<?php

use Illuminate\Support\Facades\Route;
use App\Models\MemberProfile;
use Illuminate\Support\Facades\Storage;

//PAGES
Route::view('/', 'pages.home')->name('home');

Route::view('/about', 'pages.about')->name('about');

Route::view('/membership', 'pages.membership')->name('membership');

Route::livewire('/directory', 'pages::directory')->name('directory');

Route::view('/faq', 'pages.faq')->name('faq');

Route::view('/contact', 'pages.contact')->name('contact');

Route::view('/privacy-policy', 'pages.privacy-policy')
    ->name('privacy-policy');

Route::view('/terms', 'pages.terms')
    ->name('terms');

Route::view('/code-of-ethics', 'pages.code-of-ethics')
    ->middleware(['auth', 'verified'])
    ->name('code-of-ethics');

Route::view('/best-practices', 'pages.best-practices')
    ->middleware(['auth', 'verified'])
    ->name('best-practices');

//ACCOUNT

Route::view('/account', 'pages.account')
    ->middleware(['auth', 'verified'])
    ->name('account');

Route::livewire('/account/delete', 'account.delete')
    ->middleware(['auth', 'verified'])
    ->name('account.delete');

//MEMBERSHIP

Route::livewire('/membership/join', 'membership.join')
    ->middleware(['auth', 'verified'])
    ->name('membership.join');

//PROFILES

Route::livewire('/account/profiles/create', 'member-profiles.create')
    ->middleware(['auth', 'verified'])
    ->name('member-profiles.create');

Route::livewire('/account/profiles/{profile}/edit', 'member-profiles.edit')
    ->middleware(['auth', 'verified'])
    ->name('account.profiles.edit');

Route::livewire('/directory/{profile:username}', 'member-profiles.show')
    ->name('member-profiles.show');

Route::livewire('/directory/{profile:username}/card', 'member-profiles.card')
    ->name('member-profiles.card');

Route::delete('/account/profiles/{profile}', function (MemberProfile $profile) {
    if ($profile->user_id !== auth()->id()) {
        abort(403);
    }

    if ($profile->profile_image) {
        Storage::disk('public')->delete($profile->profile_image);
    }

    $profile->delete();

    return redirect()
        ->route('account')
        ->with(
            'status',
            'Your profile has been deleted. Your IRDI account and membership were not affected.'
        );
})
    ->middleware(['auth', 'verified'])
    ->name('account.profiles.destroy');


