<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Pantry') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @include('layouts._assets')
    </head>
    <body class="font-sans text-stone-800 antialiased">
        <div class="flex min-h-screen flex-col items-center bg-stone-100 pt-10 sm:justify-center sm:pt-0">
            <div>
                <a href="/" class="text-2xl font-semibold tracking-tight text-stone-800">
                    {{ config('app.name', 'Pantry') }}
                </a>
            </div>

            <div class="mt-6 w-full max-w-md overflow-hidden rounded-2xl border border-stone-200 bg-white px-6 py-6">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
