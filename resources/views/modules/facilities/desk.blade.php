<x-layouts.app :active-nav="$navId" :title="$facilityLabel" subtitle="Record attendance and collect fees">
    <div class="mb-6 grid gap-4 sm:grid-cols-4">
        <x-ui.kpi-card label="Today" :value="$todayCount" period="visits recorded" />
        <x-ui.kpi-card label="Today" :value="$todayPeople" period="people" />
        <x-ui.kpi-card label="Period collected" :value="number_format($periodTotal, 0)" :period="config('nexstay.currency.default', 'TZS')" />
        <x-ui.kpi-card label="Default fee" :value="number_format($defaultFee, 0)" period="per visit" />
    </div>

    <div class="mb-6 grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('tenant.facilities.attendance.store') }}" class="card space-y-4 p-6" id="attendance-form">
            @csrf
            <input type="hidden" name="facility_type" value="{{ $facilityType }}">

            <h3 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Record visit</h3>

            <div>
                <label class="mb-1.5 block text-xs font-medium text-ink-muted">Visitor type</label>
                <div class="flex flex-wrap gap-3">
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="radio" name="visitor_type" value="walk_in" @checked(old('visitor_type', 'walk_in') === 'walk_in') class="text-primary">
                        Walk-in / member
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="radio" name="visitor_type" value="hotel_guest" @checked(old('visitor_type') === 'hotel_guest') class="text-primary">
                        Hotel guest
                    </label>
                </div>
            </div>

            <div id="walk-in-fields">
                <label class="mb-1.5 block text-xs font-medium text-ink-muted">Visitor name</label>
                <input type="text" name="visitor_name" value="{{ old('visitor_name') }}" class="input-field" placeholder="e.g. John Mwangi">
                <p class="mt-1 text-xs text-ink-muted">For members or visitors not registered in the system.</p>
            </div>

            <div id="hotel-guest-fields" class="hidden">
                <label class="mb-1.5 block text-xs font-medium text-ink-muted">Reservation</label>
                <select name="reservation_id" class="input-field">
                    <option value="">Select reservation…</option>
                    @foreach ($reservations as $r)
                        <option value="{{ $r->id }}" @selected(old('reservation_id') === $r->id)>
                            {{ $r->booking_ref }} — {{ $r->guest?->first_name }} {{ $r->guest?->last_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-medium text-ink-muted">Number of people</label>
                <input type="number" step="1" min="1" name="party_size" value="{{ old('party_size', 1) }}" class="input-field" id="party-size-field">
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-medium text-ink-muted">Fee amount</label>
                <input type="number" step="0.01" min="0" name="amount" value="{{ old('amount', $defaultFee) }}" class="input-field" id="amount-field">
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-medium text-ink-muted">Settlement</label>
                <select name="settlement" class="input-field" id="settlement-field">
                    <option value="cash" @selected(old('settlement', 'cash') === 'cash')>Cash</option>
                    <option value="mobile_money" @selected(old('settlement') === 'mobile_money')>Mobile money</option>
                    <option value="card" @selected(old('settlement') === 'card')>Card</option>
                    <option value="folio" @selected(old('settlement') === 'folio')>Charge to room folio</option>
                    <option value="complimentary" @selected(old('settlement') === 'complimentary')>Complimentary (free)</option>
                </select>
            </div>

            <div id="cash-fields" class="space-y-3">
                @if ($openTillSessions->isNotEmpty())
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-ink-muted">Till session (optional)</label>
                        <select name="till_session_id" class="input-field">
                            <option value="">No till — don't track in a cash drawer</option>
                            @foreach ($openTillSessions as $session)
                                <option value="{{ $session->id }}" @selected(old('till_session_id') === $session->id)>
                                    {{ $session->outlet?->name ?? 'Property till' }} — opened {{ $session->opened_at->format('H:i') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-ink-muted">Cash tendered (optional)</label>
                    <input type="number" step="0.01" min="0" name="cash_tendered" value="{{ old('cash_tendered') }}" class="input-field" placeholder="Defaults to fee amount">
                </div>
            </div>

            <div id="mobile-fields" class="hidden">
                <label class="mb-1.5 block text-xs font-medium text-ink-muted">Transaction reference</label>
                <input type="text" name="transaction_ref" value="{{ old('transaction_ref') }}" class="input-field" placeholder="M-Pesa / Tigo ref">
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-medium text-ink-muted">Notes (optional)</label>
                <input type="text" name="notes" value="{{ old('notes') }}" class="input-field">
            </div>

            <button type="submit" class="btn-primary">Record attendance</button>
        </form>

        <div class="card p-6 text-sm text-ink-muted">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-ink">How it works</h3>
            <ul class="list-disc space-y-2 pl-5">
                <li><strong>Walk-in / member</strong> — enter the visitor name directly; no guest profile required.</li>
                <li><strong>Hotel guest</strong> — link to an active reservation; charge the room folio or collect payment at desk.</li>
                <li><strong>Cash</strong> — collected at the desk; if a till session is open you can optionally link it for drawer reconciliation.</li>
                <li><strong>Complimentary</strong> — attendance is logged with zero fee.</li>
            </ul>
        </div>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="mb-1 block text-xs font-medium text-ink-muted">From</label>
            <input type="date" name="from" value="{{ $from }}" class="input-field w-auto">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-ink-muted">To</label>
            <input type="date" name="to" value="{{ $to }}" class="input-field w-auto">
        </div>
        <button type="submit" class="btn-outline">Filter</button>
    </form>

    <div class="card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-ink-muted">
                <tr>
                    <th class="px-5 py-3 text-left">Date & time</th>
                    <th class="px-5 py-3 text-left">Visitor</th>
                    <th class="px-5 py-3 text-right">People</th>
                    <th class="px-5 py-3 text-left">Type</th>
                    <th class="px-5 py-3 text-left">Settlement</th>
                    <th class="px-5 py-3 text-right">Amount</th>
                    <th class="px-5 py-3 text-left">Recorded by</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($attendances as $row)
                    <tr>
                        <td class="px-5 py-4 text-ink-muted">{{ $row->attended_at->format('d M Y H:i') }}</td>
                        <td class="px-5 py-4 font-medium">{{ $row->visitor_name }}</td>
                        <td class="px-5 py-4 text-right">{{ $row->party_size }}</td>
                        <td class="px-5 py-4">{{ $row->reservation_id ? 'Hotel guest' : 'Walk-in' }}</td>
                        <td class="px-5 py-4 capitalize">{{ str_replace('_', ' ', $row->settlement) }}</td>
                        <td class="px-5 py-4 text-right font-medium">@money($row->amount)</td>
                        <td class="px-5 py-4 text-ink-muted">{{ $row->recorder?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center text-ink-muted">No attendance recorded for this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t px-5 py-3">{{ $attendances->links() }}</div>
    </div>

    <script>
        (function () {
            const form = document.getElementById('attendance-form');
            if (!form) return;

            const visitorRadios = form.querySelectorAll('input[name="visitor_type"]');
            const walkInFields = document.getElementById('walk-in-fields');
            const hotelFields = document.getElementById('hotel-guest-fields');
            const settlement = document.getElementById('settlement-field');
            const cashFields = document.getElementById('cash-fields');
            const mobileFields = document.getElementById('mobile-fields');
            const amountField = document.getElementById('amount-field');
            const folioOption = settlement.querySelector('option[value="folio"]');

            function syncVisitorType() {
                const type = form.querySelector('input[name="visitor_type"]:checked')?.value;
                const isHotel = type === 'hotel_guest';
                walkInFields.classList.toggle('hidden', isHotel);
                hotelFields.classList.toggle('hidden', !isHotel);
                if (folioOption) {
                    folioOption.hidden = !isHotel;
                    folioOption.disabled = !isHotel;
                    if (!isHotel && settlement.value === 'folio') {
                        settlement.value = 'cash';
                    }
                }
                syncSettlement();
            }

            function syncSettlement() {
                const value = settlement.value;
                const isCash = value === 'cash';
                const isMobile = value === 'mobile_money' || value === 'card';
                const isComplimentary = value === 'complimentary';

                cashFields.classList.toggle('hidden', !isCash);
                mobileFields.classList.toggle('hidden', !isMobile);
                amountField.readOnly = isComplimentary;
                if (isComplimentary) {
                    amountField.value = '0';
                }
            }

            visitorRadios.forEach((radio) => radio.addEventListener('change', syncVisitorType));
            settlement.addEventListener('change', syncSettlement);
            syncVisitorType();
        })();
    </script>
</x-layouts.app>
