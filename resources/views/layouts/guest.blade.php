<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicons -->
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="16x16 32x32 48x48 64x64 128x128 256x256" type="image/x-icon">
        <link rel="icon" href="{{ asset('img/udg.svg') }}" type="image/svg+xml">
        <link rel="apple-touch-icon" href="{{ asset('favicon-192.png') }}">
        <link rel="manifest" href="{{ asset('site.webmanifest') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center px-4 py-10 bg-gradient-to-br from-gray-100 via-gray-50 to-gray-100">
            <div class="relative w-full sm:max-w-md">
                <div class="absolute -inset-1 bg-white/40 blur-2xl rounded-3xl"></div>

                <div class="relative rounded-2xl bg-white shadow-2xl overflow-hidden">
                    <div class="h-1.5 bg-gradient-to-r from-brand-700 via-brand-500 to-brand-400"></div>
                    <div class="px-6 py-8 sm:px-10">
                        <div class="mb-8 flex flex-col items-center">
                            <x-application-logo class="h-20 w-auto" />
                            <span class="mt-4 text-xl font-bold text-gray-800">Firmas · Control Escolar</span>
                            <span class="text-sm font-medium text-gray-500">Universidad de Guadalajara · CUCEI</span>
                        </div>

                        {{ $slot }}
                    </div>
                </div>

                <div class="mt-6 text-center text-sm text-gray-500">
                    <span>Created by <strong class="text-gray-700">CTA CUCEI</strong> · Universidad de Guadalajara</span>
                </div>
            </div>
        </div>
    </body>
</html>
