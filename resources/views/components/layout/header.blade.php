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

<header class="flex flex-wrap items-center gap-4 px-6 py-5 lg:px-8">
    <div class="min-w-0 flex-1">
        @if ($showSearch)
            <form method="GET" action="{{ route('tenant.availability') }}" class="relative block max-w-xl">
                <span class="sr-only">Search availability</span>
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-ink-subtle" />
                <input
                    type="search"
                    name="q"
                    class="input-field pl-12"
                    placeholder="Search room types, amenities…"
                    value="{{ request('q') }}"
                >
            </form>
        @else
            <h1 class="text-2xl font-bold tracking-tight text-ink">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-0.5 text-sm text-ink-muted">{{ $subtitle }}</p>
            @endif
        @endif
    </div>

    <div class="flex items-center gap-3 sm:gap-4">
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

        <div class="flex items-center gap-3 rounded-2xl py-1.5 pr-2 pl-1.5">
            <span class="flex size-10 items-center justify-center rounded-full bg-primary-soft text-sm font-bold text-primary">
                {{ $user ? strtoupper(substr($user->name, 0, 1)) : '?' }}
            </span>
            <span class="hidden text-left sm:block">
                <span class="block text-sm font-semibold text-ink">{{ $user?->name ?? 'Guest' }}</span>
                <span class="block text-xs text-ink-muted">{{ $roleLabel }}</span>
            </span>
        </div>
    </div>
</header>
