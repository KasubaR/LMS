<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Role') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-4 sm:p-8">
                @php $assigned = old('permissions', $role->permissions->pluck('name')->all()); @endphp

                <form method="POST" action="{{ route('admin.roles.update', $role) }}">
                    @csrf
                    @method('put')

                    <div>
                        <x-input-label for="name" :value="__('Role Name')" />
                        <x-text-input id="name" name="name" type="text" class="block mt-1 w-full" :value="old('name', $role->name)" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="mt-6">
                        <x-input-label :value="__('Permissions')" />
                        <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach ($permissions as $permission)
                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        @checked(collect($assigned)->contains($permission))>
                                    {{ $permission }}
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('permissions')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end mt-6 gap-3">
                        <a href="{{ route('admin.roles.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
                        <x-primary-button>{{ __('Save') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
