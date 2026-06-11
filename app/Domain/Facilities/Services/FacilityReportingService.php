<?php

declare(strict_types=1);

namespace App\Domain\Facilities\Services;

use App\Domain\Facilities\Models\FacilityAttendance;
use Carbon\Carbon;

class FacilityReportingService
{
    /**
     * @return array{
     *     facility_type: string,
     *     facility_label: string,
     *     from: string,
     *     to: string,
     *     summary: array{
     *         total_visits: int,
     *         walk_in_visits: int,
     *         hotel_guest_visits: int,
     *         complimentary_visits: int,
     *         total_collected: float,
     *         by_settlement: array<string, float>,
     *     },
     *     daily_rows: list<array{date: string, date_label: string, visits: int, collected: float}>,
     *     attendances: \Illuminate\Support\Collection,
     * }
     */
    public function attendanceReport(string $facilityType, Carbon $from, Carbon $to): array
    {
        $attendances = FacilityAttendance::query()
            ->with(['guest', 'reservation', 'recorder', 'payment'])
            ->where('facility_type', $facilityType)
            ->whereNull('voided_at')
            ->whereBetween('attended_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderByDesc('attended_at')
            ->get();

        $bySettlement = [];
        foreach (FacilityAttendance::SETTLEMENTS as $method) {
            $bySettlement[$method] = 0.0;
        }

        $walkIn    = 0;
        $hotel     = 0;
        $complimentary = 0;
        $collected = 0.0;

        foreach ($attendances as $row) {
            if ($row->reservation_id) {
                $hotel++;
            } else {
                $walkIn++;
            }

            if ($row->settlement === 'complimentary') {
                $complimentary++;
            } else {
                $collected += (float) $row->amount;
            }

            $bySettlement[$row->settlement] = ($bySettlement[$row->settlement] ?? 0) + (float) $row->amount;
        }

        $dailyRows = [];
        $cursor    = $from->copy()->startOfDay();
        $end       = $to->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $dayKey = $cursor->toDateString();
            $dayRows = $attendances->filter(
                fn (FacilityAttendance $a) => $a->attended_at->toDateString() === $dayKey
            );

            $dailyRows[] = [
                'date'       => $dayKey,
                'date_label' => $cursor->format('d M Y'),
                'visits'     => $dayRows->count(),
                'collected'  => (float) $dayRows
                    ->where('settlement', '!=', 'complimentary')
                    ->sum('amount'),
            ];

            $cursor->addDay();
        }

        return [
            'facility_type'  => $facilityType,
            'facility_label' => $this->facilityLabel($facilityType),
            'from'           => $from->toDateString(),
            'to'             => $to->toDateString(),
            'summary'        => [
                'total_visits'         => $attendances->count(),
                'walk_in_visits'       => $walkIn,
                'hotel_guest_visits'   => $hotel,
                'complimentary_visits' => $complimentary,
                'total_collected'      => $collected,
                'by_settlement'        => $bySettlement,
            ],
            'daily_rows'   => $dailyRows,
            'attendances'  => $attendances,
        ];
    }

    private function facilityLabel(string $type): string
    {
        return match ($type) {
            'pool' => 'Swimming Pool',
            'gym' => 'Gym',
            default => ucfirst($type),
        };
    }
}
