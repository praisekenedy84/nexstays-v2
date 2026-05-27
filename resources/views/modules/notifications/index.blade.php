<x-layouts.app active-nav="notifications" title="Notifications" subtitle="Property-wide activity feed">

<div class="space-y-4">

    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        {{-- Type filter --}}
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('tenant.notifications.index') }}"
               class="{{ !$type ? 'filter-pill-active' : 'filter-pill' }}">
                All
            </a>
            @foreach ($types as $t)
                <a href="{{ route('tenant.notifications.index', ['type' => $t]) }}"
                   class="{{ $type === $t ? 'filter-pill-active' : 'filter-pill' }}">
                    {{ ucwords(str_replace('_', ' ', $t)) }}
                </a>
            @endforeach
        </div>

        @if ($notifications->total() > 0)
            <form method="POST" action="{{ route('tenant.notifications.read-all') }}">
                @csrf
                <button type="submit" class="btn-outline text-xs">
                    Mark all as read
                </button>
            </form>
        @endif
    </div>

    {{-- List --}}
    <section class="card divide-y divide-slate-100 overflow-hidden">
        @forelse ($notifications as $notif)
            <div class="flex items-start gap-4 px-5 py-4 {{ $notif->isUnread() ? 'bg-primary/[0.03]' : '' }}">
                {{-- Unread dot --}}
                <div class="mt-1.5 flex size-2 shrink-0 items-center justify-center">
                    @if ($notif->isUnread())
                        <span class="block size-2 rounded-full bg-primary"></span>
                    @endif
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $notif->typeColor() }}">
                            {{ $notif->typeLabel() }}
                        </span>
                        <span class="text-xs text-ink-subtle">
                            {{ $notif->created_at->diffForHumans() }}
                        </span>
                        @if ($notif->read_at)
                            <span class="text-xs text-ink-subtle">· Read {{ $notif->read_at->diffForHumans() }}</span>
                        @endif
                    </div>
                    <p class="mt-1 font-semibold text-ink">{{ $notif->title }}</p>
                    <p class="text-sm text-ink-muted">{{ $notif->body }}</p>
                </div>

                @if ($notif->isUnread())
                    <form method="POST"
                          action="{{ route('tenant.notifications.read', $notif) }}"
                          class="shrink-0">
                        @csrf
                        <button type="submit"
                                class="rounded-lg px-2.5 py-1 text-xs font-medium text-ink-muted ring-1 ring-slate-200 hover:text-ink"
                                title="Mark as read">
                            ✓
                        </button>
                    </form>
                @endif
            </div>
        @empty
            <div class="px-5 py-12 text-center text-sm text-ink-muted">
                No notifications yet.
            </div>
        @endforelse
    </section>

    {{ $notifications->links() }}

</div>
</x-layouts.app>
