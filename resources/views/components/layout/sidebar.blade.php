@props(['active' => 'dashboard'])

<aside class="flex w-56 shrink-0 flex-col border-r border-slate-200/80 bg-white px-3 py-6">
    <a
        href="{{ route('tenant.dashboard') }}"
        class="mb-1 flex items-center gap-3 rounded-xl px-2 py-2 transition hover:bg-slate-50"
        title="{{ $tenantLabel ?? 'NexStay' }}"
    >
        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-navy text-xs font-bold tracking-wide text-white">
            {{ strtoupper(substr((string) ($tenantLabel ?? 'NS'), 0, 2)) }}
        </span>
        <span class="min-w-0">
            <span class="block truncate text-sm font-bold text-ink">NexStay</span>
            <span class="block truncate text-xs text-ink-muted" title="{{ $tenantLabel ?? '' }}">{{ $tenantLabel ?? 'Property' }}</span>
        </span>
    </a>

    <nav class="mt-6 flex flex-1 flex-col gap-0.5 overflow-y-auto" aria-label="Main navigation">
        @foreach ($hbmsNavigation ?? [] as $item)
            @if (isset($item['children']))
                @php
                    $childIds = collect($item['children'])->pluck('id');
                    $groupActive = $childIds->contains($active);
                @endphp
                <details class="nav-group" @if ($groupActive) open @endif>
                    <summary @class(['nav-item nav-group-trigger', 'nav-group-active' => $groupActive])>
                        <x-icon :name="$item['icon']" class="size-5 shrink-0" />
                        <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                        <x-icon name="chevron-down" class="nav-group-chevron size-4 shrink-0 opacity-60" />
                    </summary>
                    <div class="nav-group-children">
                        @foreach ($item['children'] as $child)
                            @php
                                $isActive = $active === $child['id'];
                                $href = Route::has($child['route']) ? route($child['route']) : '#';
                            @endphp
                            <a
                                href="{{ $href }}"
                                @class(['nav-sub-item', 'nav-sub-item-active' => $isActive])
                                @if ($isActive) aria-current="page" @endif
                            >
                                {{ $child['label'] }}
                            </a>
                        @endforeach
                    </div>
                </details>
            @else
                @php
                    $isActive = $active === $item['id'];
                    $href = Route::has($item['route']) ? route($item['route']) : '#';
                @endphp
                <a
                    href="{{ $href }}"
                    @class(['nav-item', 'nav-item-active' => $isActive])
                    @if ($isActive) aria-current="page" @endif
                >
                    <x-icon :name="$item['icon']" class="size-5 shrink-0" />
                    <span class="truncate">{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach
    </nav>

    <form method="POST" action="{{ route('tenant.logout') }}" class="mt-4 border-t border-slate-100 pt-4">
        @csrf
        <button type="submit" class="nav-item">
            <x-icon name="logout" class="size-5 shrink-0" />
            <span class="truncate">Sign out</span>
        </button>
    </form>
</aside>
