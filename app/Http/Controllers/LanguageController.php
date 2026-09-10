<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        $configuredLocales = config('locales.supported', []);
        $supported = is_array($configuredLocales) ? $configuredLocales : [];

        abort_unless(array_key_exists($locale, $supported), 404);

        $request->session()->put('locale', $locale);

        if ($request->user()) {
            $request->user()->forceFill(['locale' => $locale])->save();
        }

        cookie()->queue(cookie(
            'irdi_locale',
            $locale,
            60 * 24 * 365,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'lax',
        ));

        return back();
    }
}
