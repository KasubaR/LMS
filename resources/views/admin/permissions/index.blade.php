<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Permission Management') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-4 sm:p-6 overflow-x-auto">
                <p class="text-sm text-gray-500 mb-4">
                    {{ __('Permissions are fixed by the application. To change which roles have a permission, edit the role from Role Management.') }}
                </p>

                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 uppercase text-xs">
                            <th class="py-2 pr-4">{{ __('Permission') }}</th>
                            @foreach ($roles as $role)
                                <th class="py-2 pr-4">{{ $role->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($permissions as $permission)
                            <tr>
                                <td class="py-2 pr-4">{{ $permission->name }}</td>
                                @foreach ($roles as $role)
                                    <td class="py-2 pr-4 text-center">
                                        @if ($role->permissions->contains('name', $permission->name))
                                            <span class="text-green-600">&#10003;</span>
                                        @else
                                            <span class="text-gray-300">&mdash;</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
