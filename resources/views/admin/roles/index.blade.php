<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Role Management') }}</div>
    </x-slot>

    <div class="space-y-4">
        <div class="flex justify-between items-center flex-wrap gap-3">
            <h1 class="text-xl">{{ __('Roles') }}</h1>
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                <i class="ph ph-plus"></i>{{ __('Add Role') }}
            </a>
        </div>

        @if (session('status'))
            <div class="card elev-sm p-3 text-sm text-accent-300">{{ session('status') }}</div>
        @endif

        @if (session('error'))
            <div class="card elev-sm p-3 text-sm text-danger">{{ session('error') }}</div>
        @endif

        <div class="card elev-sm p-4 sm:p-6">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Role') }}</th>
                            <th>{{ __('Permissions') }}</th>
                            <th>{{ __('Users') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roles as $role)
                            <tr>
                                <td>{{ $role->name }}</td>
                                <td>{{ $role->permissions_count }}</td>
                                <td>{{ $role->users_count }}</td>
                                <td class="space-x-1 whitespace-nowrap">
                                    <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-ghost">{{ __('Edit') }}</a>
                                    <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="inline" onsubmit="return confirm('{{ __('Delete this role?') }}')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-danger">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <a href="{{ route('admin.permissions.index') }}" class="btn btn-ghost">
            <i class="ph ph-shield-check"></i>{{ __('View Permission Matrix') }}
        </a>
    </div>
</x-app-layout>
