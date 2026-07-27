<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Loan Management System') }}</title>

        <x-theme-boot />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/css/nocturne.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-bg text-ink auth-shell">
        <div class="auth-theme-toggle">
            <x-theme-toggle />
        </div>

        <main class="auth-shell__main">
            <div class="auth-brand">
                <div class="auth-brand__mark" aria-hidden="true">
                    <i class="ph ph-bank"></i>
                </div>
                <div>
                    <p class="auth-brand__name">{{ config('app.name', 'Loan Management System') }}</p>
                    <p class="auth-brand__tag">{{ __('Secure staff access') }}</p>
                </div>
            </div>

            <div class="auth-panel">
                {{ $slot }}
            </div>
        </main>
    </body>
</html>
