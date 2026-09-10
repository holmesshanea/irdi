<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

function registerLocaleTestRoute(): void
{
    Route::get('/_tests/locale', fn () => response(app()->getLocale()))
        ->middleware('web');
}

test('guest can switch to a supported language', function () {
    $response = $this
        ->from('/')
        ->post(route('language.switch', 'es'));

    $response
        ->assertRedirect('/')
        ->assertCookie('irdi_locale', 'es');

    expect(session('locale'))->toBe('es');
});

test('authenticated language choice is saved to the user account', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $this->actingAs($user)
        ->from('/account')
        ->post(route('language.switch', 'de'))
        ->assertRedirect('/account')
        ->assertCookie('irdi_locale', 'de');

    expect($user->fresh()->locale)->toBe('de')
        ->and(session('locale'))->toBe('de');
});

test('saved user locale is applied on later requests', function () {
    registerLocaleTestRoute();

    $user = User::factory()->create(['locale' => 'fr']);

    $this->actingAs($user)
        ->get('/_tests/locale')
        ->assertOk()
        ->assertSeeText('fr');

    expect(session('locale'))->toBe('fr');
});

test('guest locale cookie is applied and restored into the session', function () {
    registerLocaleTestRoute();

    $this->withCookie('irdi_locale', 'nl')
        ->get('/_tests/locale')
        ->assertOk()
        ->assertSeeText('nl');

    expect(session('locale'))->toBe('nl');
});

test('unsupported language cannot be selected', function () {
    $this->post(route('language.switch', 'xx'))
        ->assertNotFound();
});

test('all configured languages have the common translation file', function () {
    foreach (array_keys(config('locales.supported')) as $locale) {
        expect(lang_path($locale.'/common.php'))->toBeFile();
    }
});

test('all configured languages have the home translation file', function () {
    foreach (array_keys(config('locales.supported')) as $locale) {
        expect(lang_path($locale.'/home.php'))->toBeFile();
    }
});

test('home page renders in the selected language', function () {
    $this->withSession(['locale' => 'es'])
        ->get(route('home'))
        ->assertOk()
        ->assertSeeText('Elevando el estándar de la detección de metales responsable')
        ->assertSeeText('Nuestros principios rectores');
});

test('all configured languages have complete application and validation catalogs', function () {
    $english = json_decode(
        file_get_contents(lang_path('en.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($english)->toBeArray()->not->toBeEmpty();

    foreach (array_keys(config('locales.supported')) as $locale) {
        $jsonPath = lang_path($locale.'.json');
        $validationPath = lang_path($locale.'/validation.php');

        expect($jsonPath)->toBeFile()
            ->and($validationPath)->toBeFile();

        $catalog = json_decode(
            file_get_contents($jsonPath),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        expect(array_keys($catalog))->toEqual(array_keys($english));
    }
});

test('remaining public pages render translated content', function () {
    $this->withSession(['locale' => 'es'])
        ->get(route('about'))
        ->assertOk()
        ->assertSeeText('Acerca de IRDI');

    $this->withSession(['locale' => 'es'])
        ->get(route('membership'))
        ->assertOk()
        ->assertSeeText('Hazte miembro de IRDI');

    $this->withSession(['locale' => 'es'])
        ->get(route('faq'))
        ->assertOk()
        ->assertSeeText('Preguntas frecuentes');

    $this->withSession(['locale' => 'es'])
        ->get(route('contact'))
        ->assertOk()
        ->assertSeeText('Contactar con IRDI');
});

test('public layout renders valid schema context key', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('"@context": "https://schema.org"', false);
});

test('member preferred locale falls back to the configured default when invalid', function () {
    $user = User::factory()->make(['locale' => 'xx']);

    expect($user->preferredLocale())->toBe(config('locales.default'));
});
