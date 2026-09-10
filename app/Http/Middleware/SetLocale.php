<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredLocales = config('locales.supported', []);
        $supported = is_array($configuredLocales)
            ? array_keys($configuredLocales)
            : [];

        $default = (string) config('locales.default', config('app.locale', 'en'));

        $candidates = [
            $request->session()->get('locale'),
            $request->user()?->locale,
            $request->cookie('irdi_locale'),
            $default,
        ];

        $locale = $default;

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && in_array($candidate, $supported, true)) {
                $locale = $candidate;
                break;
            }
        }

        app()->setLocale($locale);
        Carbon::setLocale($locale);
        $request->session()->put('locale', $locale);

        if ($request->user() && $request->user()->locale !== $locale) {
            $request->user()->forceFill(['locale' => $locale])->save();
        }

        return $next($request);
    }
}
