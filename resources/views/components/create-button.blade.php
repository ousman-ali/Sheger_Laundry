@props([
    'href' => '#',
    'label' => null,
    'icon' => true,
])

<a href="{{ $href }}"
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-md shadow-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2']) }}
    aria-label="{{ $label ?? (string) $slot }}">
    @if($icon)
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    @endif
    <span>{{ $label ?? $slot }}</span>
</a>
