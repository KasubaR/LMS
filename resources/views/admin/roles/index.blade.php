<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Role Management') }}</h2>
            <a href="{{ route('admin.roles.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('Add Role') }}
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

            <div class="bg-white shadow sm:rounded-lg p-4 sm:p-6">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 uppercase text-xs">
                            <th class="py-2 pr-4">{{ __('Role') }}</th>
                            <th class="py-2 pr-4">{{ __('Permissions') }}</th>
                            <th class="py-2 pr-4">{{ __('Users') }}</th>
                            <th class="py-2 pr-4">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($roles as $role)
                            <tr>
                                <td class="py-2 pr-4">{{ $role->name }}</td>
                                <td class="py-2 pr-4">{{ $role->permissions_count }}</td>
                                <td class="py-2 pr-4">{{ $role->users_count }}</td>
                                <td class="py-2 pr-4 space-x-2 whitespace-nowrap">
                                    <a href="{{ route('admin.roles.edit', $role) }}" class="text-indigo-600 hover:underline">{{ __('Edit') }}</a>
                                    <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="inline" onsubmit="return confirm('{{ __('Delete this role?') }}')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="text-red-600 hover:underline">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
