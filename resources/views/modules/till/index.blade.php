<x-layouts.app active-nav="till" title="Till sessions" subtitle="Cash drawer lifecycle — open, movements, close">
    @can('manage-till')
        <div class="mb-4 flex justify-end">
            <a href="{{ route('tenant.till.create') }}" class="btn-primary">Open till</a>
        </div>
    @endcan

    @if ($openSessions->isNotEmpty())
        <section class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($openSessions as $session)
                <article class="card border-l-4 border-l-emerald-500 p-4">
                    <p class="text-xs font-medium uppercase text-emerald-600">Open</p>
                    <p class="font-bold text-ink">{{ $session->outlet?->name ?? 'Front desk' }}</p>
                    <p class="text-sm text-ink-muted">Float @money($session->float_amount)</p>
                    <p class="mt-1 font-mono text-xs text-ink-subtle">{{ $session->id }}</p>
                    @can('manage-till')
                        <a href="{{ route('tenant.till.close.form', $session) }}" class="mt-3 inline-block text-sm text-primary hover:underline">Close session</a>
                    @endcan
                </article>
            @endforeach
        </section>
    @endif

    <div class="card overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                <tr>
                    <th class="px-5 py-3">Outlet</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3">Opened</th>
                    <th class="px-5 py-3 text-right">Over / short</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($sessions as $session)
                    <tr>
                        <td class="px-5 py-4">{{ $session->outlet?->name ?? '—' }}</td>
                        <td class="px-5 py-4"><x-ui.status-badge :status="$session->status" /></td>
                        <td class="px-5 py-4 text-ink-muted">{{ $session->opened_at?->format('d M Y H:i') }}</td>
                        <td class="px-5 py-4 text-right">
                            @if ($session->over_short !== null)
                                @money($session->over_short)
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-5 py-4 text-right">
                            @if ($session->status === 'open')
                                @can('manage-till')
                                    <a href="{{ route('tenant.till.close.form', $session) }}" class="text-primary hover:underline">Close</a>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="border-t px-5 py-3">{{ $sessions->links() }}</div>
    </div>
    <p class="mt-4 text-xs text-ink-subtle">API: POST /api/v1/tills/open · POST /api/v1/tills/{id}/close</p>
</x-layouts.app>
