<?php

namespace App\Services\Hr;

use Carbon\Carbon;

class AttendanceHoursCalculator
{
    public function resolve(string $status, ?string $checkIn, ?string $checkOut, mixed $manualHours): ?float
    {
        if ($manualHours !== null && $manualHours !== '') {
            return round((float) $manualHours, 2);
        }

        if ($checkIn && $checkOut) {
            $in = Carbon::createFromFormat('H:i:s', strlen($checkIn) === 5 ? $checkIn.':00' : $checkIn);
            $out = Carbon::createFromFormat('H:i:s', strlen($checkOut) === 5 ? $checkOut.':00' : $checkOut);
            if ($out->lessThan($in)) {
                $out->addDay();
            }

            return round($in->diffInMinutes($out) / 60, 2);
        }

        return match ($status) {
            'present' => (float) config('hr.default_working_hours', 8),
            'half_day' => (float) config('hr.half_day_working_hours', 4),
            'paid_leave', 'holiday' => (float) config('hr.default_working_hours', 8),
            default => 0.0,
        };
    }
}