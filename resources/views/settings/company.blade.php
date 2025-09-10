<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Company & Invoice Settings</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if (session('success'))
                    <div class="mb-4 p-3 bg-green-50 text-green-800 border border-green-200 rounded">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 p-3 bg-red-50 text-red-800 border border-red-200 rounded">
                        <ul class="list-disc ml-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('settings.company.update') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium">Company Name</label>
                        <input type="text" name="company_name" value="{{ old('company_name',$settings['company_name']) }}" class="w-full border rounded p-2" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Address</label>
                        <textarea name="company_address" class="w-full border rounded p-2" rows="2">{{ old('company_address',$settings['company_address']) }}</textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium">Phone</label>
                            <input type="text" name="company_phone" value="{{ old('company_phone',$settings['company_phone']) }}" class="w-full border rounded p-2" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Email</label>
                            <input type="email" name="company_email" value="{{ old('company_email',$settings['company_email']) }}" class="w-full border rounded p-2" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium">TIN</label>
                            <input type="text" name="company_tin" value="{{ old('company_tin',$settings['company_tin']) }}" class="w-full border rounded p-2" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium">VAT Reg. No</label>
                            <input type="text" name="company_vat_no" value="{{ old('company_vat_no',$settings['company_vat_no']) }}" class="w-full border rounded p-2" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Company Logo</label>
                        @if(!empty($settings['company_logo_url']))
                            <div class="mb-2">
                                <img src="{{ $settings['company_logo_url'] }}" alt="Logo" class="h-12 inline-block rounded border" />
                            </div>
                        @endif
                        <input type="file" name="company_logo_file" accept="image/*" class="w-full border rounded p-2" />
                        <input type="url" name="company_logo_url" placeholder="https://..." value="{{ old('company_logo_url',$settings['company_logo_url']) }}" class="w-full border rounded p-2 mt-2" />
                        <label class="inline-flex items-center gap-2 mt-2 text-sm"><input type="checkbox" name="remove_logo" value="1"> Remove current logo</label>
                        <p class="text-xs text-gray-500">Upload a file or provide a URL. Uploaded file overrides the URL. Removing clears the logo.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Company Stamp</label>
                        @if(!empty($settings['company_stamp_url']))
                            <div class="mb-2">
                                <img src="{{ $settings['company_stamp_url'] }}" alt="Stamp" class="h-12 inline-block rounded border" />
                            </div>
                        @endif
                        <input type="file" name="company_stamp_file" accept="image/*" class="w-full border rounded p-2" />
                        <input type="url" name="company_stamp_url" placeholder="https://..." value="{{ old('company_stamp_url',$settings['company_stamp_url']) }}" class="w-full border rounded p-2 mt-2" />
                        <label class="inline-flex items-center gap-2 mt-2 text-sm"><input type="checkbox" name="remove_stamp" value="1"> Remove current stamp</label>
                        <p class="text-xs text-gray-500">Upload a file or provide a URL. Uploaded file overrides the URL. Removing clears the stamp.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Invoice Footer Text</label>
                        <textarea name="invoice_footer" class="w-full border rounded p-2" rows="2">{{ old('invoice_footer',$settings['invoice_footer']) }}</textarea>
                    </div>
                    <div class="flex gap-2">
                        <button class="bg-gray-800 text-white px-4 py-2 rounded">Save</button>
                        <a class="border px-4 py-2 rounded" href="{{ route('dashboard') }}">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
