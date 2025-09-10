@if ($errors->any())
    <div class="mb-4">
        <div class="font-medium text-red-600">{{ __('Whoops! Something went wrong.') }}</div>
        <ul class="mt-3 list-disc list-inside text-sm text-red-600">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- Name -->
<div class="mt-4">
    <x-input-label for="name" :value="__('Name')" />
    <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $user->name ?? '')" required autofocus />
</div>

<!-- Email Address -->
<div class="mt-4">
    <x-input-label for="email" :value="__('Email')" />
    <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $user->email ?? '')" required />
</div>

<!-- Phone -->
<div class="mt-4">
    <x-input-label for="phone" :value="__('Phone')" />
    <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone', $user->phone ?? '')" required />
</div>

<!-- Password -->
<div class="mt-4">
    <x-input-label for="password" :value="__('Password')" />
    <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" />
    @if (isset($user))
        <small class="text-gray-500">Leave blank to keep the current password.</small>
    @endif
</div>

<!-- Confirm Password -->
<div class="mt-4">
    <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
    <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" />
</div>

<!-- Roles -->
<div class="mt-4">
    <x-input-label for="roles" :value="__('Roles')" />
    @php
        $userRoles = isset($user) ? $user->roles->pluck('id')->toArray() : [];
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-2">
        @foreach($roles as $id => $name)
            <label class="flex items-center">
                <input type="checkbox" name="roles[]" value="{{ $id }}"
                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                       {{ in_array($id, old('roles', $userRoles)) ? 'checked' : '' }}>
                <span class="ml-2 text-sm text-gray-600">{{ ucfirst($name) }}</span>
            </label>
        @endforeach
    </div>
</div>


<div class="flex items-center justify-end mt-4">
    <a href="{{ route('users.index') }}" class="text-sm text-gray-600 hover:text-gray-900 mr-4">
        {{ __('Cancel') }}
    </a>

    <x-primary-button>
        {{ isset($user) ? __('Update User') : __('Create User') }}
    </x-primary-button>
</div>