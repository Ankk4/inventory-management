<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#f5f5f4">

        <title>{{ config('app.name', 'Pantry') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @include('layouts._assets')
    </head>
    <body class="font-sans antialiased bg-stone-100 text-stone-800">
        <div class="min-h-screen flex flex-col">
            @isset($inventory)
                @include('layouts.top-bar', [
                    'inventory' => $inventory,
                    'inventories' => $inventories ?? collect(),
                ])
            @else
                <header class="sticky top-0 z-30 border-b border-stone-200 bg-stone-50/95 backdrop-blur">
                    <div class="mx-auto flex h-14 max-w-xl items-center justify-between px-4">
                        <span class="text-base font-semibold tracking-tight">{{ config('app.name', 'Pantry') }}</span>
                        <a href="{{ route('profile.edit') }}" class="text-sm text-stone-500 hover:text-stone-800">Profile</a>
                    </div>
                </header>
            @endisset

            <main class="mx-auto w-full max-w-xl flex-1 px-4 pb-28 pt-4">
                {{ $slot }}
            </main>

            @isset($inventory)
                @include('layouts.tab-bar', ['inventory' => $inventory])
            @endisset
        </div>
    </body>
</html>
