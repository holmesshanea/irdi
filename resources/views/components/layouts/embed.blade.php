<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? 'IRDI Member' }}</title>

@vite(['resources/css/app.css', 'resources/js/app.js'])

@livewireStyles

@fluxAppearance
</head>

<body class="bg-white text-zinc-900 antialiased">

{{ $slot }}

@livewireScripts

</body>

</html>
