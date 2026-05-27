<div class="relative" id="notif-bell-wrapper">

    {{-- Bell button --}}
    <button type="button"
            id="notif-bell-btn"
            aria-label="Notifications"
            class="relative flex size-9 items-center justify-center rounded-full text-ink-muted transition hover:bg-slate-100 hover:text-ink">
        <x-icon name="bell" class="size-5" />
        @if ($unreadCount > 0)
            <span class="absolute top-0.5 right-0.5 flex size-4 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-white leading-none">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    {{-- Dropdown --}}
    <div id="notif-dropdown"
         class="absolute right-0 z-50 mt-2 hidden w-80 origin-top-right rounded-xl border border-slate-200 bg-white shadow-xl"
         role="dialog"
         aria-label="Recent notifications">

        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <span class="text-sm font-semibold text-ink">Notifications</span>
            <div class="flex items-center gap-2">
                @if ($unreadCount > 0)
                    <form method="POST" action="{{ route('tenant.notifications.read-all') }}">
                        @csrf
                        <button type="submit" class="text-xs font-medium text-primary hover:underline">
                            Mark all read
                        </button>
                    </form>
                    <span class="text-slate-300">|</span>
                @endif
                <a href="{{ route('tenant.notifications.index') }}"
                   class="text-xs font-medium text-ink-muted hover:text-ink">
                    View all
                </a>
            </div>
        </div>

        <ul class="max-h-80 divide-y divide-slate-100 overflow-y-auto">
            @forelse ($recent as $notif)
                <li class="flex items-start gap-3 px-4 py-3 {{ $notif->isUnread() ? 'bg-primary/[0.04]' : '' }}">
                    @if ($notif->isUnread())
                        <span class="mt-1.5 block size-2 shrink-0 rounded-full bg-primary"></span>
                    @else
                        <span class="mt-1.5 block size-2 shrink-0"></span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-ink">{{ $notif->title }}</p>
                        <p class="mt-0.5 truncate text-xs text-ink-muted">{{ $notif->body }}</p>
                        <p class="mt-1 text-[10px] text-ink-subtle">{{ $notif->created_at->diffForHumans() }}</p>
                    </div>
                    @if ($notif->isUnread())
                        <form method="POST"
                              action="{{ route('tenant.notifications.read', $notif) }}"
                              class="shrink-0">
                            @csrf
                            <button type="submit"
                                    class="mt-1 rounded px-1.5 py-0.5 text-xs text-ink-subtle hover:text-primary"
                                    title="Mark read">✓</button>
                        </form>
                    @endif
                </li>
            @empty
                <li class="px-4 py-8 text-center text-sm text-ink-muted">
                    No notifications yet.
                </li>
            @endforelse
        </ul>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    const btn      = document.getElementById('notif-bell-btn');
    const dropdown = document.getElementById('notif-dropdown');
    const wrapper  = document.getElementById('notif-bell-wrapper');

    if (!btn || !dropdown) return;

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.toggle('hidden');
    });

    document.addEventListener('click', function (e) {
        if (!wrapper.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            dropdown.classList.add('hidden');
        }
    });
})();
</script>
@endpush
@endonce
