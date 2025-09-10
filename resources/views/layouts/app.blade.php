<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', \App\Models\SystemSetting::getValue('company_name', config('app.name', 'Sheger Automatic Laundry')))</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <style>[x-cloak]{ display:none !important; }</style>
    <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @auth
        <script>
            // Public Echo config (no secrets). Override via .env (Vite) or here.
            window.APP_USER_ID = {{ (int)auth()->id() }};
            window.ECHO_CONFIG = {
                key: @json(config('broadcasting.connections.pusher.key')),
                cluster: @json(config('broadcasting.connections.pusher.options.cluster')),
                wsHost: @json(config('broadcasting.connections.pusher.options.host') ?? null),
                wsPort: @json(config('broadcasting.connections.pusher.options.port') ?? null),
                wssPort: @json(config('broadcasting.connections.pusher.options.port') ?? null),
                forceTLS: @json((config('broadcasting.connections.pusher.options.scheme', 'https') === 'https')),
            };
        </script>
        @endauth
    </head>
    <body class="font-sans antialiased">
    <div class="h-screen bg-gray-100 flex flex-col" x-data="{ sidebarCollapsed: JSON.parse(localStorage.getItem('sidebarCollapsed') || 'false'), toggleSidebar(){ this.sidebarCollapsed = !this.sidebarCollapsed; localStorage.setItem('sidebarCollapsed', JSON.stringify(this.sidebarCollapsed)); } }">
            @php $isOperator = auth()->check() && auth()->user()->hasRole('Operator'); @endphp
            @include('layouts.navigation')

            <!-- Page Heading (component or section) -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @elseif(View::hasSection('content'))
                @if(View::hasSection('title'))
                    <header class="bg-white shadow">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            <h2 class="font-semibold text-xl text-gray-800 leading-tight">@yield('title')</h2>
                        </div>
                    </header>
                @endif
            @endisset

            <!-- Page Content with optional sidebar for Admin/Receptionist/Manager -->
            <div class="flex flex-1">
                @unless($isOperator)
                    @include('layouts.sidebar')
                @endunless
                <main class="flex-1 overflow-auto">
                    @include('partials.flash')
                    <x-confirm />
                    @yield('content')
                    @isset($slot)
                        {{ $slot }}
                    @endisset
                </main>
            </div>
        </div>
        <script>
            function __initConfirmStore(){
                if (!window.Alpine) { return false; }
                if (Alpine.store('confirm')) { return true; }
                Alpine.store('confirm', {
                    show: false,
                    title: 'Are you sure?',
                    message: 'This action cannot be undone.',
                    okText: 'Confirm',
                    cancelText: 'Cancel',
                    onConfirm: null,
                    open(opts = {}) {
                        this.title = opts.title || this.title;
                        this.message = opts.message || this.message;
                        this.okText = opts.okText || this.okText;
                        this.cancelText = opts.cancelText || this.cancelText;
                        this.onConfirm = typeof opts.onConfirm === 'function' ? opts.onConfirm : null;
                        this.show = true;
                    },
                    close() { this.show = false; },
                    confirmAndClose() {
                        const cb = this.onConfirm; this.show = false; if (cb) cb();
                    }
                });
                return true;
            }
            function __wireConfirmHandlers(){
                // Wire up data-confirm on buttons/links
                document.addEventListener('click', (e) => {
                    const btn = e.target.closest('[data-confirm]');
                    if (!btn) return;
                    // If the matched element is a FORM, do not handle in click phase.
                    // Let the 'submit' handler manage confirmations for forms.
                    if (btn.tagName === 'FORM') return;
                    const message = btn.getAttribute('data-confirm') || 'Are you sure?';
                    const title = btn.getAttribute('data-confirm-title') || 'Please Confirm';
                    const ok = btn.getAttribute('data-confirm-ok') || 'Yes';
                    const cancel = btn.getAttribute('data-confirm-cancel') || 'No';
                    const formId = btn.getAttribute('data-confirm-form');
                    const href = btn.getAttribute('href');

                    const hasStore = !!(window.Alpine && Alpine.store && Alpine.store('confirm'));

                    if (!hasStore) {
                        // Fallback to native confirm to avoid breaking core actions
                        const confirmed = window.confirm(message);
                        if (!confirmed) {
                            e.preventDefault();
                            return;
                        }
                        if (formId) {
                            e.preventDefault();
                            const f = document.getElementById(formId);
                            if (f) f.submit();
                            return;
                        }
                        // Otherwise, allow default behavior (submit/link navigation)
                        return;
                    }

                    e.preventDefault();
                    Alpine.store('confirm').open({
                        title, message, okText: ok, cancelText: cancel,
                        onConfirm: () => {
                            const submitForm = (f) => { f.dataset.confirmResolved = '1'; setTimeout(() => f.submit(), 0); };
                            if (formId) { const f = document.getElementById(formId); if (f) submitForm(f); return; }
                            if (btn.tagName === 'BUTTON' && (btn.type === 'submit' || btn.getAttribute('type') === null)) {
                                const f = btn.form || btn.closest('form'); if (f) submitForm(f); return;
                            }
                            if (href) { window.location.href = href; }
                        }
                    });
                });
                // Wire up data-confirm on forms
                document.addEventListener('submit', (e) => {
                    const form = e.target;
                    if (!(form instanceof HTMLFormElement)) return;
                    if (form.dataset.confirmResolved === '1') {
                        // Clear the guard and allow submit to proceed without re-confirming
                        delete form.dataset.confirmResolved; return;
                    }
                    const msg = form.getAttribute('data-confirm');
                    if (!msg) return;
                    const hasStore = !!(window.Alpine && Alpine.store && Alpine.store('confirm'));
                    if (!hasStore) {
                        // Native confirm fallback for forms
                        const confirmed = window.confirm(msg);
                        if (!confirmed) {
                            e.preventDefault();
                        }
                        return;
                    }
                    e.preventDefault();
                    const title = form.getAttribute('data-confirm-title') || 'Please Confirm';
                    const ok = form.getAttribute('data-confirm-ok') || 'Proceed';
                    const cancel = form.getAttribute('data-confirm-cancel') || 'Cancel';
                    Alpine.store('confirm').open({
                        title, message: msg, okText: ok, cancelText: cancel,
                        onConfirm: () => { form.dataset.confirmResolved = '1'; setTimeout(() => form.submit(), 0); }
                    });
                }, true);
            }
            // Initialize when Alpine is ready or retry shortly if needed
            document.addEventListener('alpine:init', () => { __initConfirmStore(); __wireConfirmHandlers(); });
            // Fallback in case alpine:init is missed or Alpine loads after this script
            (function ensureInit(retries = 40){ // ~2s total with 50ms interval
                if (__initConfirmStore()) { __wireConfirmHandlers(); return; }
                if (retries <= 0) return;
                setTimeout(() => ensureInit(retries - 1), 50);
            })();
        </script>
    </body>
</html>
