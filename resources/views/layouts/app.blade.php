<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'AttendEase') }} – @yield('title')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
        @theme {
            --font-sans: 'Inter', ui-sans-serif, system-ui, sans-serif;
            --color-primary: #3B5BDB;
            --color-primary-dark: #1E3A8A;
            --color-primary-50: #EDF2FF;
            --color-primary-100: #E0E7FF;
            --color-sidebar: #1E293B;
            --color-sidebar-dark: #0F172A;
            --color-present: #16A34A;
            --color-present-50: #F0FDF4;
            --color-late: #D97706;
            --color-late-50: #FFFBEB;
            --color-absent: #DC2626;
            --color-absent-50: #FEF2F2;
            --color-excused: #7C3AED;
            --color-excused-50: #F5F3FF;
        }
    </style>
</head>
<body class="min-h-screen bg-slate-100 flex">

    {{-- Sidebar --}}
    <aside class="w-60 min-h-screen bg-sidebar flex flex-col flex-shrink-0 fixed top-0 left-0 z-10">

        {{-- Logo --}}
        <div class="h-[72px] bg-sidebar-dark flex items-center gap-3 px-5 flex-shrink-0">
            <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center flex-shrink-0">
                <span class="text-white font-bold text-sm">A</span>
            </div>
            <span class="text-white font-semibold text-base">AttendEase</span>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-3 pt-5 space-y-0.5">
            @php
                $navItems = [
                    ['route' => 'dashboard', 'label' => 'Dashboard'],
                    ['route' => 'classes.index', 'label' => 'My Classes'],
                    ['route' => 'attendance.index', 'label' => 'Attendance'],
                    ['route' => 'reports.index', 'label' => 'Reports'],
                ];
            @endphp

            @foreach ($navItems as $item)
                @php
                    $active = request()->routeIs($item['route'] === 'dashboard' ? 'dashboard' : $item['route'].'*');
                    $href = Route::has($item['route']) ? route($item['route']) : '#';
                @endphp
                <a href="{{ $href }}"
                   class="flex items-center gap-2.5 px-4 h-11 rounded-lg text-sm transition-colors
                          {{ $active
                              ? 'bg-primary text-white font-semibold'
                              : 'text-white/60 hover:text-white hover:bg-white/5' }}">
                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0
                                 {{ $active ? 'bg-white' : 'bg-white/35' }}"></span>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        {{-- User / Logout --}}
        <div class="px-4 py-5 border-t border-white/10 flex-shrink-0">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-white text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                    <p class="text-white/45 text-xs mt-0.5">Administrator</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="text-white/45 hover:text-white text-xs transition-colors flex-shrink-0">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main (offset by sidebar width) --}}
    <div class="flex-1 flex flex-col min-h-screen ml-60">

        {{-- Top header --}}
        <header class="h-16 bg-white border-b border-slate-200 flex items-center px-8 flex-shrink-0 sticky top-0 z-[5]">
            <h1 class="text-slate-800 font-semibold text-lg">@yield('title')</h1>
            @hasSection('header-actions')
                <div class="ml-auto flex items-center gap-3">
                    @yield('header-actions')
                </div>
            @endif
        </header>

        {{-- Page content --}}
        <main class="flex-1 p-8">
            @yield('content')
        </main>
    </div>

    {{-- Confirmation Modal --}}
    <div id="confirm-modal"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden"
         role="dialog" aria-modal="true">
        {{-- Backdrop --}}
        <div id="confirm-backdrop" class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>

        {{-- Dialog --}}
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
            <div id="confirm-icon" class="w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4"></div>
            <h3 id="confirm-title" class="text-slate-800 font-semibold text-base text-center mb-1"></h3>
            <p id="confirm-message" class="text-slate-500 text-sm text-center mb-6"></p>
            <div class="flex gap-3">
                <button id="confirm-cancel"
                        class="flex-1 px-4 py-2.5 border border-slate-300 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50 transition-colors">
                    Cancel
                </button>
                <button id="confirm-ok"
                        class="flex-1 px-4 py-2.5 text-white text-sm font-semibold rounded-lg transition-colors">
                    Confirm
                </button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modal     = document.getElementById('confirm-modal');
            const backdrop  = document.getElementById('confirm-backdrop');
            const iconEl    = document.getElementById('confirm-icon');
            const titleEl   = document.getElementById('confirm-title');
            const messageEl = document.getElementById('confirm-message');
            const cancelBtn = document.getElementById('confirm-cancel');
            const okBtn     = document.getElementById('confirm-ok');

            let pendingForm = null;

            function openModal(form) {
                const isDanger  = form.dataset.confirmDanger !== undefined;
                const title     = form.dataset.confirmTitle   || 'Are you sure?';
                const message   = form.dataset.confirmMessage || 'This action cannot be undone.';

                titleEl.textContent   = title;
                messageEl.textContent = message;

                if (isDanger) {
                    iconEl.className    = 'w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4 bg-red-100';
                    iconEl.innerHTML    = '<svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>';
                    okBtn.className     = 'flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors';
                    okBtn.textContent   = form.dataset.confirmOk || 'Delete';
                } else {
                    iconEl.className    = 'w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4 bg-amber-100';
                    iconEl.innerHTML    = '<svg class="w-6 h-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
                    okBtn.className     = 'flex-1 px-4 py-2.5 bg-slate-700 hover:bg-slate-800 text-white text-sm font-semibold rounded-lg transition-colors';
                    okBtn.textContent   = form.dataset.confirmOk || 'Confirm';
                }

                pendingForm = form;
                modal.classList.remove('hidden');
            }

            function closeModal() {
                modal.classList.add('hidden');
                pendingForm = null;
            }

            cancelBtn.addEventListener('click', closeModal);
            backdrop.addEventListener('click', closeModal);
            document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

            okBtn.addEventListener('click', function () {
                if (pendingForm) {
                    modal.classList.add('hidden');
                    pendingForm.submit();
                }
            });

            document.addEventListener('submit', function (e) {
                const form = e.target;
                if (!form.hasAttribute('data-confirm')) return;
                e.preventDefault();
                openModal(form);
            });
        })();
    </script>

    @stack('scripts')
</body>
</html>
