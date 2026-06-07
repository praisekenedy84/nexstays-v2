<div class="relative" id="notif-bell-wrapper">

    {{-- Bell button --}}
    <button type="button"
            id="notif-bell-btn"
            aria-label="Notifications"
            aria-expanded="false"
            class="relative flex size-9 items-center justify-center rounded-full text-ink-muted transition hover:bg-slate-100 hover:text-ink">
        <x-icon name="bell" class="size-5" />
        <span id="notif-badge"
              class="absolute top-0.5 right-0.5 hidden size-4 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-white leading-none">
        </span>
    </button>

    {{-- Dropdown --}}
    <div id="notif-dropdown"
         class="absolute right-0 z-50 mt-2 hidden w-80 origin-top-right rounded-xl border border-slate-200 bg-white shadow-xl"
         role="dialog"
         aria-label="Recent notifications">

        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <span class="text-sm font-semibold text-ink">Notifications</span>
            <div class="flex items-center gap-2">
                <form id="notif-read-all-form" method="POST" action="{{ route('tenant.notifications.read-all') }}" class="hidden">
                    @csrf
                    <button type="submit" class="text-xs font-medium text-primary hover:underline">
                        Mark all read
                    </button>
                </form>
                <span id="notif-read-all-sep" class="hidden text-slate-300">|</span>
                <a href="{{ route('tenant.notifications.index') }}"
                   class="text-xs font-medium text-ink-muted hover:text-ink">
                    View all
                </a>
            </div>
        </div>

        <ul id="notif-list" class="max-h-80 divide-y divide-slate-100 overflow-y-auto">
            <li class="px-4 py-8 text-center text-sm text-ink-muted">
                Open to load notifications.
            </li>
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
    const badge    = document.getElementById('notif-badge');
    const list     = document.getElementById('notif-list');
    const readAll  = document.getElementById('notif-read-all-form');
    const readSep  = document.getElementById('notif-read-all-sep');
    const previewUrl = @json(route('tenant.notifications.preview'));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    if (!btn || !dropdown || !list) return;

    let loaded = false;
    let loading = false;

    const renderBadge = (count) => {
        if (!badge) return;

        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.classList.remove('hidden');
            badge.classList.add('flex');
            readAll?.classList.remove('hidden');
            readSep?.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
            badge.classList.remove('flex');
            readAll?.classList.add('hidden');
            readSep?.classList.add('hidden');
        }
    };

    const renderList = (items) => {
        if (!items.length) {
            list.innerHTML = '<li class="px-4 py-8 text-center text-sm text-ink-muted">No notifications yet.</li>';
            return;
        }

        list.innerHTML = items.map((item) => {
            const unreadClass = item.is_unread ? 'bg-primary/[0.04]' : '';
            const dot = item.is_unread
                ? '<span class="mt-1.5 block size-2 shrink-0 rounded-full bg-primary"></span>'
                : '<span class="mt-1.5 block size-2 shrink-0"></span>';
            const markRead = item.is_unread
                ? `<form method="POST" action="${item.read_url}" class="shrink-0"><input type="hidden" name="_token" value="${csrf ?? ''}"><button type="submit" class="mt-1 rounded px-1.5 py-0.5 text-xs text-ink-subtle hover:text-primary" title="Mark read">✓</button></form>`
                : '';

            return `<li class="flex items-start gap-3 px-4 py-3 ${unreadClass}">${dot}<div class="min-w-0 flex-1"><p class="truncate text-sm font-medium text-ink">${item.title}</p><p class="mt-0.5 truncate text-xs text-ink-muted">${item.body}</p><p class="mt-1 text-[10px] text-ink-subtle">${item.created_at}</p></div>${markRead}</li>`;
        }).join('');
    };

    const loadPreview = async () => {
        if (loading || loaded) return;
        loading = true;
        list.innerHTML = '<li class="px-4 py-8 text-center text-sm text-ink-muted">Loading…</li>';

        try {
            const response = await fetch(previewUrl, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('Failed to load notifications.');
            }

            const payload = await response.json();
            renderBadge(Number(payload.unread_count ?? 0));
            renderList(Array.isArray(payload.recent) ? payload.recent : []);
            loaded = true;
        } catch (error) {
            list.innerHTML = '<li class="px-4 py-8 text-center text-sm text-ink-muted">Could not load notifications.</li>';
        } finally {
            loading = false;
        }
    };

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        const opening = dropdown.classList.contains('hidden');
        dropdown.classList.toggle('hidden');
        btn.setAttribute('aria-expanded', opening ? 'true' : 'false');

        if (opening) {
            loadPreview();
        }
    });

    document.addEventListener('click', function (e) {
        if (!wrapper.contains(e.target)) {
            dropdown.classList.add('hidden');
            btn.setAttribute('aria-expanded', 'false');
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            dropdown.classList.add('hidden');
            btn.setAttribute('aria-expanded', 'false');
        }
    });
})();
</script>
@endpush
@endonce
