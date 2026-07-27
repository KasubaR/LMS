<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Login History') }}</div>
    </x-slot>

    <div class="space-y-4">
        <div class="flex justify-between items-center flex-wrap gap-3">
            <h1 class="text-xl">{{ __('Login History') }}</h1>

            <a href="{{ route('audit-logs.index') }}" class="btn btn-ghost">
                <i class="ph ph-clock-counter-clockwise"></i>
                {{ __('Audit Logs') }}
            </a>
        </div>

        <div class="card elev-sm p-4 sm:p-6">
            <form method="GET" action="{{ route('audit-logs.login-history.index') }}" class="list-filters">
                <div class="list-filters__field">
                    <x-input-label for="user_id" :value="__('User')" />
                    <select id="user_id" name="user_id" class="input mt-1">
                        <option value="">{{ __('All users') }}</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((int) ($filters['user_id'] ?? 0) === (int) $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="list-filters__field list-filters__field--grow">
                    <x-input-label for="email" :value="__('Email contains')" />
                    <x-text-input id="email" name="email" type="search" class="block mt-1 w-full" :value="$filters['email'] ?? ''" placeholder="{{ __('user@example.com…') }}" />
                </div>

                <div class="list-filters__field">
                    <x-input-label for="failed_only" :value="__('Failed only')" />
                    <select id="failed_only" name="failed_only" class="input mt-1">
                        <option value="0" @selected(empty($filters['failed_only']))>{{ __('No') }}</option>
                        <option value="1" @selected(! empty($filters['failed_only']))>{{ __('Yes') }}</option>
                    </select>
                </div>

                <div class="list-filters__field">
                    <x-input-label for="date_from" :value="__('From')" />
                    <x-text-input id="date_from" name="date_from" type="date" class="block mt-1 w-full" :value="$filters['date_from'] ?? ''" />
                </div>

                <div class="list-filters__field">
                    <x-input-label for="date_to" :value="__('To')" />
                    <x-text-input id="date_to" name="date_to" type="date" class="block mt-1 w-full" :value="$filters['date_to'] ?? ''" />
                </div>

                <div class="list-filters__actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
                        {{ __('Apply') }}
                    </button>
                    <a href="{{ route('audit-logs.login-history.index') }}" class="btn btn-ghost">{{ __('Clear') }}</a>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('Email / User') }}</th>
                        <th>{{ __('Login Time') }}</th>
                        <th>{{ __('Logout Time') }}</th>
                        <th>{{ __('Failed') }}</th>
                        <th>{{ __('Browser') }}</th>
                        <th>{{ __('Device') }}</th>
                        <th>{{ __('IP') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($histories as $history)
                        <tr>
                            <td>
                                @if ($history->user)
                                    <a href="{{ route('admin.users.edit', $history->user) }}" class="text-accent-100 hover:underline">
                                        {{ $history->user->name }}
                                    </a>
                                    <span class="text-muted-500 block text-xs">{{ $history->email }}</span>
                                @else
                                    {{ $history->email }}
                                @endif
                            </td>
                            <td class="font-mono text-xs">{{ $history->login_at?->format('d M Y H:i') }}</td>
                            <td class="font-mono text-xs">{{ $history->logout_at?->format('d M Y H:i') ?: '—' }}</td>
                            <td>
                                @if ($history->failed)
                                    <span class="tag tag-danger">{{ __('Yes') }}</span>
                                    <div class="text-xs text-muted-500 mt-1">{{ $history->failure_reason ?: '—' }}</div>
                                @else
                                    <span class="tag tag-accent">{{ __('No') }}</span>
                                @endif
                            </td>
                            <td>{{ $history->browser ?: '—' }}</td>
                            <td>{{ $history->device ?: '—' }}</td>
                            <td class="font-mono text-xs">{{ $history->ip_address ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-sm text-muted-500">{{ __('No login attempts found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $histories->links() }}
        </div>
    </div>
</x-app-layout>

