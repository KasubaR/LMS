<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('User Management') }}
            </h2>
            <a href="{{ route('admin.users.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('Add User') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="p-4 bg-green-50 text-green-800 rounded-md text-sm">{{ session('status') }}</div>
            @endif

            @if (session('error'))
                <div class="p-4 bg-red-50 text-red-800 rounded-md text-sm">{{ session('error') }}</div>
            @endif

            @if (session('generated_password'))
                <div class="p-4 bg-yellow-50 border border-yellow-300 text-yellow-900 rounded-md text-sm">
                    {{ __('Temporary password (shown once — copy it now and relay it to the user):') }}
                    <span class="font-mono font-bold select-all">{{ session('generated_password') }}</span>
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg p-4 sm:p-6">
                <form method="GET" class="flex flex-wrap gap-3 mb-4 items-end">
                    <div>
                        <x-input-label for="role" :value="__('Role')" />
                        <select id="role" name="role" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1">
                            <option value="">{{ __('All roles') }}</option>
                            @foreach ($roles as $roleName)
                                <option value="{{ $roleName }}" @selected(request('role') === $roleName)>{{ $roleName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1">
                            <option value="">{{ __('Any status') }}</option>
                            <option value="active" @selected(request('status') === 'active')>{{ __('Active') }}</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>{{ __('Inactive') }}</option>
                        </select>
                    </div>
                    <x-secondary-button type="submit">{{ __('Filter') }}</x-secondary-button>
                </form>

                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 uppercase text-xs">
                            <th class="py-2 pr-4">{{ __('Name') }}</th>
                            <th class="py-2 pr-4">{{ __('Email') }}</th>
                            <th class="py-2 pr-4">{{ __('Role') }}</th>
                            <th class="py-2 pr-4">{{ __('Status') }}</th>
                            <th class="py-2 pr-4">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($users as $user)
                            <tr>
                                <td class="py-2 pr-4">{{ $user->name }}</td>
                                <td class="py-2 pr-4">{{ $user->email }}</td>
                                <td class="py-2 pr-4">{{ $user->roles->pluck('name')->join(', ') }}</td>
                                <td class="py-2 pr-4">
                                    @if ($user->is_active)
                                        <span class="px-2 py-1 rounded-full bg-green-100 text-green-800 text-xs">{{ __('Active') }}</span>
                                    @else
                                        <span class="px-2 py-1 rounded-full bg-gray-200 text-gray-700 text-xs">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                                <td class="py-2 pr-4 space-x-2 whitespace-nowrap">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="text-indigo-600 hover:underline">{{ __('Edit') }}</a>

                                    <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" class="inline" onsubmit="return confirm('{{ __('Generate a new temporary password for this user?') }}')">
                                        @csrf
                                        <button type="submit" class="text-indigo-600 hover:underline">{{ __('Reset Password') }}</button>
                                    </form>

                                    @if ($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}" class="inline">
                                            @csrf
                                            @method('patch')
                                            <button type="submit" class="text-indigo-600 hover:underline">
                                                {{ $user->is_active ? __('Deactivate') : __('Activate') }}
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
