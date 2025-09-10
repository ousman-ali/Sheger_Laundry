<div x-data x-cloak>
    <div x-show="$store.confirm.show" style="display:none;" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50" @click="$store.confirm.close()"></div>
    <div x-show="$store.confirm.show" style="display:none;" class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4"
             x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
            <div class="p-5">
                <div class="flex items-start gap-3">
                    <div class="shrink-0 mt-0.5">
                        <svg class="h-6 w-6 text-amber-600" viewBox="0 0 24 24" fill="currentColor"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900" x-text="$store.confirm.title"></h3>
                        <p class="mt-1 text-sm text-gray-600" x-text="$store.confirm.message"></p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" @click="$store.confirm.close()" class="px-3 py-2 rounded border border-gray-300 text-gray-700 hover:bg-gray-50" x-text="$store.confirm.cancelText"></button>
                    <button type="button" @click="$store.confirm.confirmAndClose()" class="px-3 py-2 rounded bg-red-600 text-white hover:bg-red-700" x-text="$store.confirm.okText"></button>
                </div>
            </div>
        </div>
    </div>
</div>
