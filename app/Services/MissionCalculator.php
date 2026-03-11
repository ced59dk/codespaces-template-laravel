<?php

namespace App\Services;

use App\Models\Mission;
use Carbon\Carbon;

class MissionCalculator
{
    public function computeQuantity(Mission $mission): float
    {
        $service = $mission->service;

        if (! $service) {
            return 0.0;
        }

        return match ($service->unit_type) {
            'fixed' => 1.0,
            'day'   => $this->daysQuantity($mission->start_at, $mission->end_at),
            default => $this->hoursQuantityQuarter($mission->start_at, $mission->end_at),
        };
    }

    private function hoursQuantityQuarter(?Carbon $start, ?Carbon $end): float  
{
    if (! $start || ! $end) {
        return 0.0;
    }

    // si fin avant début => incohérent
    if ($end->lessThan($start)) {
        return 0.0;
    }

    $minutes = $start->diffInMinutes($end);
    $quarters = (int) ceil($minutes / 15);

    return round($quarters * 0.25, 2);
}   

    private function daysQuantity(?Carbon $start, ?Carbon $end): float
    {
        if (! $start || ! $end) {
            return 0.0;
        }

        if ($end->lessThan($start)) {
            return 0.0;
        }

        $startDate = $start->copy()->startOfDay();
        $endDate = $end->copy()->startOfDay();

        $days = $startDate->diffInDays($endDate);

        return (float) ($days + 1);
    }
}