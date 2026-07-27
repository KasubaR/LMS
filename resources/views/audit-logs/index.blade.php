<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Audit Logs') }}</div>
    </x-slot>

    <div class="space-y-4">
        <div class="flex justify-between items-center flex-wrap gap-3">
            <h1 class="text-xl">{{ __('Audit Logs') }}</h1>

            <div class="flex items-center gap-2">
                <a
                    href="{{ route('audit-logs.login-history.index') }}"
                    class="btn btn-ghost {{ request()->routeIs('audit-logs.login-history.index') ? 'bg-accent-800 text-accent-100' : '' }}"
                >
                    <i class="ph ph-clock-counter-clockwise"></i>
                    {{ __('Login History') }}
                </a>
            </div>
        </div>

        <div class="card elev-sm p-4 sm:p-6">
            <form method="GET" action="{{ route('audit-logs.index') }}" class="list-filters">
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

                <div class="list-filters__field">
                    <x-input-label for="module" :value="__('Module')" />
                    <select id="module" name="module" class="input mt-1">
                        <option value="" @selected(($filters['module'] ?? '') === '')>{{ __('All modules') }}</option>
                        @foreach ($modules as $m)
                            <option value="{{ $m }}" @selected(($filters['module'] ?? '') === $m)>{{ (string) $m }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="list-filters__field">
                    <x-input-label for="action" :value="__('Action contains')" />
                    <x-text-input id="action" name="action" type="text" class="input mt-1" :value="$filters['action'] ?? ''" placeholder="{{ __('e.g. Loan Approved') }}" />
                </div>

                <div class="list-filters__field list-filters__field--grow">
                    <x-input-label for="q" :value="__('Search')" />
                    <x-text-input id="q" name="q" type="search" class="block mt-1 w-full" :value="$filters['q'] ?? ''" placeholder="{{ __('Description or subject type…') }}" />
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
                    <a href="{{ route('audit-logs.index') }}" class="btn btn-ghost">{{ __('Clear') }}</a>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('User') }}</th>
                        <th>{{ __('Action') }}</th>
                        <th>{{ __('Module') }}</th>
                        <th>{{ __('Record') }}</th>
                        <th>{{ __('Previous') }}</th>
                        <th>{{ __('New') }}</th>
                        <th>{{ __('IP') }}</th>
                        <th>{{ __('Browser') }}</th>
                        <th>{{ __('Device') }}</th>
                        <th>{{ __('Date & Time') }}</th>
                        <th>{{ __('Changes') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($activities as $row)
                        <tr>
                            <td>{{ $row['causer_name'] }}</td>
                            <td>{{ $row['action'] }}</td>
                            <td>{{ $row['module'] }}</td>
                            <td>
                                @if (! empty($row['record_url']))
                                    <a href="{{ $row['record_url'] }}" class="text-accent-100 hover:underline">
                                        {{ $row['record_label'] }}
                                    </a>
                                @else
                                    {{ $row['record_label'] }}
                                @endif
                            </td>
                            <td class="font-mono text-xs">{{ $row['previous'] }}</td>
                            <td class="font-mono text-xs">{{ $row['new'] }}</td>
                            <td class="font-mono text-xs">{{ $row['ip_address'] ?: '—' }}</td>
                            <td>{{ $row['browser'] ?: '—' }}</td>
                            <td>{{ $row['device'] ?: '—' }}</td>
                            <td>{{ $row['created_at']?->format('d M Y H:i') }}</td>
                            <td>
                                @if (count($row['diffs']) > 0)
                                    <details class="group">
                                        <summary class="cursor-pointer text-accent-100 hover:underline">
                                            {{ __('View') }}
                                        </summary>
                                        <div class="mt-2 text-sm space-y-2">
                                            @foreach ($row['diffs'] as $diff)
                                                <div>
                                                    <div class="font-medium">{{ $diff['attribute'] }}</div>
                                                    <div class="text-muted-500">
                                                        {{ __('From') }}: <span class="font-mono">{{ $diff['old'] === null ? '—' : $diff['old'] }}</span>
                                                        {{ __('To') }}: <span class="font-mono">{{ $diff['new'] === null ? '—' : $diff['new'] }}</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-sm text-muted-500">{{ __('No audit activities found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $activities->links() }}
        </div>
    </div>
</x-app-layout>

