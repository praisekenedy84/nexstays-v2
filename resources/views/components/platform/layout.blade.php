@props(['title' => null, 'activeNav' => ''])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? "{$title} — NexStay Platform" : 'NexStay Platform' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="flex w-56 shrink-0 flex-col border-r border-white/5 bg-slate-900 px-3 py-6">
            <div class="mb-6 flex items-center gap-3 px-2">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold tracking-wide text-white">NS</span>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-bold text-white">NexStay</span>
                    <span class="block truncate text-xs text-slate-400">Platform Admin</span>
                </span>
            </div>

            <nav class="flex flex-1 flex-col gap-0.5">
                <a href="{{ route('platform.tenants.index') }}"
                   @class(['flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition',
                           'bg-indigo-600 text-white' => $activeNav === 'tenants',
                           'text-slate-400 hover:bg-white/5 hover:text-white' => $activeNav !== 'tenants'])>
                    <svg class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M3 7l9-4 9 4M4 7v14M20 7v14M9 21V12h6v9"/>
                    </svg>
                    Hotels
                </a>
            </nav>

            <form method="POST" action="{{ route('platform.logout') }}" class="border-t border-white/5 pt-4">
                @csrf
                <button type="submit"
                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-400 transition hover:bg-white/5 hover:text-white">
                    <svg class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Sign out
                </button>
            </form>
        </aside>

        {{-- Main content --}}
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex items-center justify-between border-b border-white/5 bg-slate-900/50 px-8 py-4">
                <div>
                    @if($title)
                        <h1 class="text-base font-semibold text-white">{{ $title }}</h1>
                    @endif
                </div>
                <span class="text-xs text-slate-500">{{ auth('platform_admin')->user()?->name }}</span>
            </header>

            <main class="flex-1 overflow-auto px-8 py-6">
                @if (session('success'))
                    <div class="mb-6 rounded-xl bg-emerald-900/40 px-4 py-3 text-sm text-emerald-300 ring-1 ring-emerald-700/50">
                        {{ session('success') }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
