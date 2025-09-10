<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Penalty Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if (session('success'))
                    <div class="mb-4 p-3 rounded bg-green-50 text-green-800 text-sm">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 p-3 rounded bg-red-50 text-red-700 text-sm">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form method="POST" action="{{ route('settings.penalty.update') }}" class="space-y-4">
                    @csrf
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="penalties_enabled" value="1" class="rounded" {{ old('penalties_enabled', (int)config('shebar.penalties_enabled', 1)) ? 'checked' : '' }}>
                        <label class="text-sm">Enable penalties</label>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Penalty Daily Rate</label>
                        <input type="number" step="0.01" name="penalty_daily_rate" class="w-full border rounded p-2" value="{{ old('penalty_daily_rate', $rate) }}" required />
                        <p class="text-xs text-gray-600 mt-1">Used to compute overdue penalties when order-specific values are not set.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Grace Days (no penalty)</label>
                        <input type="number" min="0" step="1" name="penalty_grace_days" class="w-full border rounded p-2" value="{{ old('penalty_grace_days', (int)config('shebar.penalty_grace_days', 0)) }}" />
                        <p class="text-xs text-gray-600 mt-1">Number of days after pickup_date before penalties start.</p>
                    </div>
                    <div class="border-t pt-4">
                        <div class="font-medium">Service-level overrides (optional)</div>
                        <p class="text-xs text-gray-600 mb-2">Set per-day penalty rate per service. Leave blank to use default rate.</p>
                        <div class="max-h-60 overflow-auto border rounded">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="p-2 text-left">Service</th>
                                        <th class="p-2 text-right">Per-day Penalty (ETB)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(\App\Models\Service::orderBy('name')->get() as $svc)
                                        @php $val = optional(\App\Models\SystemSetting::where('key','penalty_rate_service_'.$svc->id)->first())->value; @endphp
                                        <tr class="border-b">
                                            <td class="p-2">{{ $svc->name }}</td>
                                            <td class="p-2 text-right">
                                                <input type="number" step="0.01" name="penalty_rate_service_{{ $svc->id }}" value="{{ old('penalty_rate_service_'.$svc->id, $val) }}" class="border rounded p-1 w-32 text-right" placeholder="—" />
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button class="bg-blue-600 text-white px-4 py-2 rounded">Save</button>
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded border">Back</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
