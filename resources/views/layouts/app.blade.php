<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <x-theme-boot />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/css/nocturne.css', 'resources/css/app-form.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-bg text-ink">
        <div class="min-h-screen flex" x-data="{ sidebarOpen: false }">
            @include('layouts.navigation')

            <div class="flex-1 min-w-0 flex flex-col lg:pl-0">
                <header class="sticky top-0 z-10 h-16 flex-none flex items-center justify-between gap-4 px-6 border-b border-divider bg-bg">
                    <div class="flex items-center gap-3 min-w-0">
                        <button class="lg:hidden btn btn-secondary btn-icon" @click="sidebarOpen = !sidebarOpen" aria-label="{{ __('Toggle menu') }}">
                            <i class="ph ph-list text-lg"></i>
                        </button>
                        <div class="min-w-0 truncate">
                            {{ $header ?? '' }}
                        </div>
                    </div>

                    <div class="flex items-center gap-3 flex-none min-w-0">
                        <x-global-search />

                        <x-theme-toggle />

                        <x-dropdown align="right" width="72">
                            <x-slot name="trigger">
                                <button class="btn btn-icon btn-secondary" aria-label="{{ __('Notifications') }}">
                                    <i class="ph ph-bell text-lg"></i>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <div class="px-3 py-2 text-[11px] uppercase tracking-wide text-muted-500 font-heading">{{ __('Notifications') }}</div>
                                <div class="px-3 py-4 text-sm text-muted-500">{{ __('No notifications yet.') }}</div>
                            </x-slot>
                        </x-dropdown>

                        <x-dropdown align="right" width="56">
                            <x-slot name="trigger">
                                <button class="flex items-center gap-2 rounded-md px-1 py-1 hover-surface transition-colors">
                                    <div class="w-8 h-8 rounded-full bg-accent-800 text-accent-100 flex items-center justify-center text-xs font-heading font-medium flex-none">
                                        {{ collect(explode(' ', auth()->user()->name))->map(fn ($n) => mb_substr($n, 0, 1))->take(2)->implode('') }}
                                    </div>
                                    <div class="hidden sm:flex flex-col items-start leading-tight">
                                        <span class="text-sm">{{ auth()->user()->name }}</span>
                                        <span class="text-xs text-muted-500">{{ auth()->user()->roles->first()?->name }}</span>
                                    </div>
                                    <i class="ph ph-caret-down text-xs text-muted-500"></i>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('profile.edit')">
                                    <i class="ph ph-user"></i> {{ __('Profile') }}
                                </x-dropdown-link>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')"
                                            onclick="event.preventDefault(); this.closest('form').submit();">
                                        <i class="ph ph-sign-out"></i> {{ __('Log Out') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="flex-1 px-6 py-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
