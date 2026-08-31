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
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100 flex flex-col">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white border-b border-gray-100">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                <script>
                    window.__flash = {
                        success: {{ session('success') ? json_encode(session('success')) : 'null' }},
                        error: {{ session('error') ? json_encode(session('error')) : 'null' }},
                        info: {{ session('info') ? json_encode(session('info')) : 'null' }}
                    };
                </script>

                @if($errors->any() && !$errors->has('captcha') && !$errors->has('firma_data'))
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                        <div class="rounded-md bg-red-50 border border-red-200 p-4 text-sm text-red-800">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                {{ $slot }}
            </main>

            <!-- Footer -->
            <footer class="mt-auto bg-white border-t border-gray-200">
                <div class="max-w-7xl mx-auto px-4 py-5 flex flex-col sm:flex-row items-center justify-center gap-1 text-sm text-gray-500">
                    <img src="{{ asset('img/udg.svg') }}" alt="UDG" class="h-8 w-auto mr-2">
                    <span>Created by <strong class="text-gray-700">CTA CUCEI</strong> · Universidad de Guadalajara</span>
                </div>
            </footer>
        </div>

        @stack('scripts')
    </body>
</html>
