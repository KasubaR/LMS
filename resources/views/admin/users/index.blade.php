<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('User Management') }}</div>
    </x-slot>

    <div class="space-y-4">
        <div class="flex justify-between items-center flex-wrap gap-3">
            <h1 class="text-xl">{{ __('Users') }}</h1>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                <i class="ph ph-user-plus"></i>{{ __('Add User') }}
            </a>
        </div>

        @if (session('status'))
            <div class="card elev-sm p-3 text-sm text-accent-300">{{ session('status') }}</div>
        @endif

        @if (session('error'))
            <div class="card elev-sm p-3 text-sm text-danger">{{ session('error') }}</div>
        @endif

        @if (session('generated_password'))
            <div class="card elev-sm p-3 text-sm">
                {{ __('Temporary password (shown once — copy it now and relay it to the user):') }}
                <span class="font-mono font-bold select-all text-accent-300">{{ session('generated_password') }}</span>
            </div>
        @endif

        <div class="card elev-sm p-4 sm:p-6">
            <form method="GET" class="flex flex-wrap gap-3 mb-4 items-end">
                <div>
                    <x-input-label for="role" :value="__('Role')" />
                    <select id="role" name="role" class="input mt-1">
                        <option value="">{{ __('All roles') }}</option>
                        @foreach ($roles as $roleName)
                            <option value="{{ $roleName }}" @selected(request('role') === $roleName)>{{ $roleName }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="status" :value="__('Status')" />
                    <select id="status" name="status" class="input mt-1">
                        <option value="">{{ __('Any status') }}</option>
                        <option value="active" @selected(request('status') === 'active')>{{ __('Active') }}</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>{{ __('Inactive') }}</option>
                    </select>
                </div>
                <x-secondary-button type="submit">{{ __('Filter') }}</x-secondary-button>
            </form>

            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Email') }}</th>
                            <th>{{ __('Role') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->roles->pluck('name')->join(', ') }}</td>
                                <td>
                                    @if ($user->is_active)
                                        <span class="tag tag-accent">{{ __('Active') }}</span>
                                    @else
                                        <span class="tag tag-neutral">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                                <td class="space-x-1 whitespace-nowrap">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-ghost">{{ __('Edit') }}</a>

                                    <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" class="inline" onsubmit="return confirm('{{ __('Generate a new temporary password for this user?') }}')">
                                        @csrf
                                        <button type="submit" class="btn btn-ghost">{{ __('Reset Password') }}</button>
                                    </form>

                                    @if ($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}" class="inline">
                                            @csrf
                                            @method('patch')
                                            <button type="submit" class="btn btn-ghost">
                                                {{ $user->is_active ? __('Deactivate') : __('Activate') }}
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
