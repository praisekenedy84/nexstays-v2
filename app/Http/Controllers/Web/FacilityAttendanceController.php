<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Facilities\Actions\RecordFacilityAttendance;
use App\Domain\Facilities\Actions\VoidFacilityAttendance;
use App\Domain\Facilities\Models\FacilityAttendance;
use App\Domain\HBMS\Models\Reservation;
use App\Domain\Shared\Services\FacilitySettingsService;
use App\Domain\Till\Models\TillSession;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\RecordFacilityAttendanceRequest;
use App\Http\Requests\Web\VoidFacilityAttendanceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FacilityAttendanceController extends Controller
{
    public function pool(Request $request): View
    {
        return $this->desk($request, 'pool');
    }

    public function gym(Request $request): View
    {
        return $this->desk($request, 'gym');
    }

    public function store(RecordFacilityAttendanceRequest $request): RedirectResponse
    {
        $facilityType = $request->validated('facility_type');

        try {
            app(RecordFacilityAttendance::class)->execute($request->validated());
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $route = $facilityType === 'gym'
            ? 'tenant.facilities.gym'
            : 'tenant.facilities.pool';

        return redirect()
            ->route($route)
            ->with('success', 'Attendance recorded.');
    }

    public function void(VoidFacilityAttendanceRequest $request, FacilityAttendance $attendance): RedirectResponse
    {
        $route = $attendance->facility_type === 'gym'
            ? 'tenant.facilities.gym'
            : 'tenant.facilities.pool';

        try {
            app(VoidFacilityAttendance::class)->execute($attendance, $request->validated('reason'));
        } catch (\DomainException $e) {
            return redirect()->route($route)->with('error', $e->getMessage());
        }

        return redirect()->route($route)->with('success', 'Attendance record voided.');
    }

    public function receipt(FacilityAttendance $attendance): View
    {
        $attendance->load(['guest', 'reservation', 'recorder', 'payment']);

        return view('modules.facilities.receipt', ['attendance' => $attendance]);
    }

    private function desk(Request $request, string $facilityType): View
    {
        $from = $request->filled('from')
            ? $request->date('from')
            : now()->startOfDay()->toDateString();
        $to = $request->filled('to')
            ? $request->date('to')
            : now()->toDateString();

        $attendances = FacilityAttendance::query()
            ->with(['guest', 'reservation', 'recorder'])
            ->where('facility_type', $facilityType)
            ->whereNull('voided_at')
            ->whereDate('attended_at', '>=', $from)
            ->whereDate('attended_at', '<=', $to)
            ->orderByDesc('attended_at')
            ->paginate(25)
            ->withQueryString();

        $periodTotal = FacilityAttendance::query()
            ->where('facility_type', $facilityType)
            ->whereNull('voided_at')
            ->where('settlement', '!=', 'complimentary')
            ->whereDate('attended_at', '>=', $from)
            ->whereDate('attended_at', '<=', $to)
            ->sum('amount');

        $todayCount = FacilityAttendance::query()
            ->where('facility_type', $facilityType)
            ->whereNull('voided_at')
            ->whereDate('attended_at', now()->toDateString())
            ->count();

        $todayPeople = FacilityAttendance::query()
            ->where('facility_type', $facilityType)
            ->whereNull('voided_at')
            ->whereDate('attended_at', now()->toDateString())
            ->sum('party_size');

        return view('modules.facilities.desk', [
            'facilityType' => $facilityType,
            'facilityLabel' => $facilityType === 'gym' ? 'Gym' : 'Swimming Pool',
            'navId' => $facilityType === 'gym' ? 'facility-gym' : 'facility-pool',
            'defaultFee' => app(FacilitySettingsService::class)->defaultFeeFor($facilityType),
            'attendances' => $attendances,
            'periodTotal' => $periodTotal,
            'todayCount' => $todayCount,
            'todayPeople' => $todayPeople,
            'from' => $from,
            'to' => $to,
            'reservations' => Reservation::query()
                ->with('guest')
                ->whereIn('status', ['checked_in', 'confirmed'])
                ->orderBy('check_in_date')
                ->get(),
            'openTillSessions' => TillSession::query()
                ->with('outlet')
                ->where('status', 'open')
                ->orderBy('opened_at')
                ->get(),
        ]);
    }
}
