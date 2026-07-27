<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Permission Management') }}</div>
    </x-slot>

    <div class="card elev-sm p-4 sm:p-6 overflow-x-auto">
        <p class="text-sm text-muted-500 mb-4">
            {{ __('Permissions are fixed by the application. To change which roles have a permission, edit the role from Role Management.') }}
        </p>

        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('Permission') }}</th>
                    @foreach ($roles as $role)
                        <th>{{ $role->name }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($permissions as $permission)
                    <tr>
                        <td>{{ $permission->name }}</td>
                        @foreach ($roles as $role)
                            <td class="text-center">
                                @if ($role->permissions->contains('name', $permission->name))
                                    <span class="text-accent-300">&#10003;</span>
                                @else
                                    <span class="text-muted-600">&mdash;</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
