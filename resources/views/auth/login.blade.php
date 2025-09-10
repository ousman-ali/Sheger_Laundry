<!DOCTYPE html>
<html lang="{{ str_replace('_','-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - {{ \App\Models\SystemSetting::getValue('company_name', config('app.name', 'Sheger Automatic Laundry')) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex">
    <!-- Left Side Image -->
    <div class="hidden md:flex w-1/2 h-screen bg-cover bg-center" style="background-image: url('/login-bg.jpg')">
        <!-- You can also use <img> if needed -->
    </div>

    <!-- Right Side Login Form -->
    <div class="flex items-center justify-center w-full md:w-1/2 bg-white">
        <div class="p-8 w-full max-w-md">
            <div class="flex justify-center mb-0">
    <img src="{{ \App\Models\SystemSetting::getValue('company_logo_url', asset('logo.png')) }}" alt="{{ \App\Models\SystemSetting::getValue('company_name', config('app.name', 'Sheger Automatic Laundry')) }}" class="h-26 w-auto max-w-[180px]" />
</div>

            
            <p class="text-center text-gray-600 mb-6">Please login to your account</p>

            @if (session('status'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" />
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input id="password" name="password" type="password" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" />
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember & Forgot -->
                <div class="flex items-center justify-between">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="remember" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-600">Remember me</span>
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-sm text-indigo-600 hover:underline">Forgot password?</a>
                    @endif
                </div>

                <!-- Submit -->
                <button type="submit"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Log In
                </button>
            </form>
        </div>
    </div>
</body>

</html>
