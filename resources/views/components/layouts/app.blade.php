@props([
    'activeNav' => 'dashboard',
    'title' => null,
    'subtitle' => null,
    'showHeaderSearch' => false,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? "{$title} — NexStay" : 'NexStay' }}</title>

    {{-- Apply dark class before CSS renders to prevent flash of wrong theme --}}
    <script>
        (function () {
            var theme = localStorage.getItem('nexstay-theme');
            if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen">
    {{-- Impersonation banner --}}
    @if (session('impersonating'))
        @php $imp = session('impersonating'); @endphp
        <div class="flex items-center justify-between bg-amber-400 px-5 py-2 text-xs font-medium text-amber-950">
            <span>
                Viewing as <strong>{{ $imp['user_name'] }}</strong> ({{ $imp['user_username'] ?? $imp['user_email'] }})
                at <strong>{{ $imp['tenant_name'] }}</strong>
                &nbsp;·&nbsp; Platform admin: {{ $imp['platform_admin'] }}
            </span>
            <form method="POST" action="{{ route('platform.impersonate.stop') }}">
                @csrf
                <button type="submit"
                    class="rounded-lg bg-amber-900/20 px-3 py-1 font-semibold transition hover:bg-amber-900/30">
                    Exit &rarr;
                </button>
            </form>
        </div>
    @endif

    <div class="flex min-h-screen">
        <x-layout.sidebar :active="$activeNav" />

        <div class="flex min-w-0 flex-1 flex-col">
            @isset($header)
                {{ $header }}
            @else
                <x-layout.header
                    :title="$title ?? ''"
                    :subtitle="$subtitle"
                    :show-search="$showHeaderSearch"
                />
            @endisset

            <main class="flex-1 overflow-auto px-6 pb-8 pt-2 lg:px-8">
                <x-ui.flash />
                {{ $slot }}
            </main>
        </div>
    </div>
    @stack('scripts')
    <script>
    // Refresh CSRF token every 10 minutes so long-open forms don't get 419
    (function () {
        const REFRESH_MS = 10 * 60 * 1000;
        async function refreshCsrf() {
            try {
                const res  = await fetch('/csrf-token', { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                const token = data.token;
                if (!token) return;
                document.querySelectorAll('meta[name="csrf-token"]').forEach(m => m.content = token);
                document.querySelectorAll('input[name="_token"]').forEach(i => i.value = token);
            } catch (_) {}
        }
        setInterval(refreshCsrf, REFRESH_MS);
    })();
    </script>
</body>
</html>
