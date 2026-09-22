@props([
    'title' => '',
    'subtitle' => null,
    'showSearch' => false,
])

@php
    $user = auth()->user();
    $roleLabel = $user?->roles->first()?->name
        ? str_replace('_', ' ', ucwords($user->roles->first()->name, '_'))
        : 'Staff';
@endphp

<header class="sticky top-0 z-30 flex flex-wrap items-center gap-3 border-b border-slate-200/60 bg-surface/90 px-4 py-3 pt-[max(0.75rem,env(safe-area-inset-top))] backdrop-blur-md dark:border-slate-700/50 sm:gap-4 sm:px-6 sm:py-4 lg:static lg:border-0 lg:bg-transparent lg:px-8 lg:py-5 lg:pt-5 lg:backdrop-blur-none">
    <button
        type="button"
        class="nav-open-btn -ml-1 rounded-lg p-2 text-ink-muted transition hover:bg-slate-100 hover:text-ink dark:hover:bg-slate-700/50 lg:hidden"
        aria-label="Open menu"
        aria-controls="app-sidebar"
        aria-expanded="false"
        data-nav-open
    >
        <x-icon name="menu" class="size-5" />
    </button>

    <div class="min-w-0 flex-1">
        @if ($showSearch)
            <form method="GET" action="{{ route('tenant.availability') }}" class="relative block max-w-xl">
                <span class="sr-only">Search availability</span>
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-ink-subtle sm:left-4" />
                <input
                    type="search"
                    name="q"
                    class="input-field pl-10 sm:pl-12"
                    placeholder="Search room types, amenities…"
                    value="{{ request('q') }}"
                >
            </form>
        @else
            <h1 class="truncate text-lg font-bold tracking-tight text-ink sm:text-2xl">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-0.5 truncate text-xs text-ink-muted sm:text-sm">{{ $subtitle }}</p>
            @endif
        @endif
    </div>

    <div class="flex shrink-0 items-center gap-1.5 sm:gap-3">
        <a
            href="{{ route('api.v1.health') }}"
            target="_blank"
            rel="noopener"
            class="hidden rounded-lg px-2 py-1 text-xs font-medium text-ink-muted ring-1 ring-slate-200 hover:text-ink dark:ring-slate-700 lg:inline"
            title="Tenant API health"
        >API</a>

        {{-- Dark / light mode toggle --}}
        <button
            id="theme-toggle"
            type="button"
            class="rounded-lg p-2 text-ink-muted transition hover:bg-slate-100 hover:text-ink dark:hover:bg-slate-700/50"
            title="Toggle dark mode"
            aria-label="Toggle dark mode"
        >
            <x-icon name="moon" class="size-5 dark:hidden" />
            <x-icon name="sun" class="size-5 hidden dark:block" />
        </button>

        <x-layout.notification-bell />

        <div class="flex items-center gap-2 rounded-2xl py-1 pr-1 pl-1 sm:gap-3 sm:pr-2 sm:pl-1.5">
            <span class="flex size-9 items-center justify-center rounded-full bg-primary-soft text-sm font-bold text-primary sm:size-10">
                {{ $user ? strtoupper(substr($user->name, 0, 1)) : '?' }}
            </span>
            <span class="hidden text-left sm:block">
                <span class="block text-sm font-semibold text-ink">{{ $user?->name ?? 'Guest' }}</span>
                <span class="block text-xs text-ink-muted">{{ $roleLabel }}</span>
            </span>
        </div>
    </div>
</header>
