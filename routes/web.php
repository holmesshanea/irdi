<?php

use App\Http\Controllers\LanguageController;
use App\Http\Controllers\PrivateProfileImageController;
use App\Http\Controllers\PropertyReviewInvitationController;
use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Pages
|--------------------------------------------------------------------------
*/

Route::post('/language/{locale}', LanguageController::class)
    ->name('language.switch');

Route::get('/', function () {
    return view('pages.home', [
        'memberCount' => User::query()
            ->where('membership_status', 'active')
            ->count(),
    ]);
})->name('home');

Route::view('/about', 'pages.about')
    ->name('about');

Route::view('/membership', 'pages.membership')
    ->name('membership');

Route::livewire('/directory', 'pages::directory')
    ->name('directory');

Route::livewire('/statistics', 'pages::statistics')
    ->name('statistics');

Route::view('/resources', 'pages.resources')
    ->name('resources');

Route::view('/faq', 'pages.faq')
    ->name('faq');

Route::view('/member-manual', 'pages.member-manual')
    ->name('member-manual');

Route::view('/code-of-ethics', 'pages.code-of-ethics')
    ->name('code-of-ethics');

Route::view('/best-practices', 'pages.best-practices')
    ->name('best-practices');

Route::view('/contact', 'pages.contact')
    ->name('contact');

Route::view('/privacy-policy', 'pages.privacy-policy')
    ->name('privacy-policy');

Route::view('/terms', 'pages.terms')
    ->name('terms');

Route::view('/cookie-policy', 'pages.cookie-policy')
    ->name('cookie-policy');

Route::view('/accessibility', 'pages.accessibility')
    ->name('accessibility');

Route::get('/sitemap.xml', function () {
    $pages = [
        [
            'url' => route('home'),
            'priority' => '1.0',
        ],
        [
            'url' => route('about'),
            'priority' => '0.8',
        ],
        [
            'url' => route('membership'),
            'priority' => '0.9',
        ],
        [
            'url' => route('directory'),
            'priority' => '0.9',
        ],
        [
            'url' => route('statistics'),
            'priority' => '0.8',
        ],
        [
            'url' => route('faq'),
            'priority' => '0.8',
        ],
        [
            'url' => route('contact'),
            'priority' => '0.6',
        ],

        [
            'url' => route('accessibility'),
            'priority' => '0.3',
        ],

        [
            'url' => route('privacy-policy'),
            'priority' => '0.3',
        ],
        [
            'url' => route('terms'),
            'priority' => '0.3',
        ],

        [
            'url' => route('cookie-policy'),
            'priority' => '0.3',
        ],
    ];

    $profiles = MemberProfile::query()
        ->with('user')
        ->publicDirectory()
        ->get();

    $xml = view('sitemap', [
        'pages' => $pages,
        'profiles' => $profiles,
    ])->render();

    return Response::make($xml, 200, [
        'Content-Type' => 'application/xml',
    ]);
})->name('sitemap');

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

    Route::get('/account/profiles/{profile}/private-image', PrivateProfileImageController::class)
        ->name('account.profiles.private-image');

    Route::view('/account', 'pages.account')
        ->name('account');

    Route::get('/account/delete', function () {
        abort(403);
    })->name('account.delete');

    Route::livewire('/account/reviews', 'pages::account.reviews')
        ->name('account.reviews');

    /*
    |--------------------------------------------------------------------------
    | Membership
    |--------------------------------------------------------------------------
    */

    Route::livewire('/membership/join', 'membership.join')
        ->name('membership.join');

    /*
    |--------------------------------------------------------------------------
    | Member Profiles
    |--------------------------------------------------------------------------
    */

    Route::livewire('/account/profiles/create', 'member-profiles.create')
        ->name('member-profiles.create');

    Route::livewire('/account/profiles/{profile}/edit', 'member-profiles.edit')
        ->name('account.profiles.edit');

    Route::delete('/account/profiles/{profile}', function () {
        abort(403);
    })->name('account.profiles.destroy');

    /*
    |--------------------------------------------------------------------------
    | Messages
    |--------------------------------------------------------------------------
    */

    Route::livewire(
        '/messages',
        'messages.index'
    )->name('messages.index');

    Route::livewire(
        '/messages/{profile:username}/create',
        'messages.create'
    )->name('messages.create');

    Route::livewire(
        '/messages/{message}',
        'messages.show'
    )->name('messages.show');

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
    ->middleware(['auth', 'verified', 'moderator-or-admin'])
    ->name('admin.reviews.index');

Route::livewire('/admin/reviews/{review}', 'admin.reviews.show')
    ->middleware(['auth', 'verified', 'moderator-or-admin'])
    ->name('admin.reviews.show');

/*
|--------------------------------------------------------------------------
| Staff Activity Log
|--------------------------------------------------------------------------
*/

Route::livewire(
    '/admin/activity',
    'admin.activity.index'
)
    ->middleware(['auth', 'verified', 'moderator-or-admin'])
    ->name('admin.activity.index');

/*
|--------------------------------------------------------------------------
| Message Reports
|--------------------------------------------------------------------------
*/

Route::livewire(
    '/admin/message-reports',
    'admin.message-reports.index'
)
    ->middleware(['auth', 'verified', 'moderator-or-admin'])
    ->name('admin.message-reports.index');

Route::livewire(
    '/admin/message-reports/{report}',
    'admin.message-reports.show'
)
    ->middleware(['auth', 'verified', 'moderator-or-admin'])
    ->name('admin.message-reports.show');

/*
|--------------------------------------------------------------------------
| Member Management
|--------------------------------------------------------------------------
*/

Route::livewire(
    '/admin/members',
    'admin.members.index'
)
    ->middleware(['auth', 'verified', 'moderator-or-admin'])
    ->name('admin.members.index');

Route::livewire(
    '/admin/members/{user}',
    'admin.members.show'
)
    ->middleware(['auth', 'verified', 'moderator-or-admin'])
    ->name('admin.members.show');
