@if (session('success'))
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <div class="rounded-md bg-green-50 p-4 border border-green-200">
            <div class="flex">
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    </div>
@endif

@if (session('error'))
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <div class="rounded-md bg-red-50 p-4 border border-red-200">
            <div class="flex">
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    </div>
@endif

@if (session('status'))
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <div class="rounded-md bg-blue-50 p-4 border border-blue-200">
            <div class="flex">
                <div class="ml-3">
                    <p class="text-sm font-medium text-blue-800">{{ session('status') }}</p>
                </div>
            </div>
        </div>
    </div>
@endif

@if ($errors->any())
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <div class="rounded-md bg-yellow-50 p-4 border border-yellow-200">
            <div class="flex">
                <div class="ml-3">
                    <p class="text-sm font-medium text-yellow-800">Please fix the following errors:</p>
                    <ul class="mt-2 list-disc list-inside text-sm text-yellow-900">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endif
