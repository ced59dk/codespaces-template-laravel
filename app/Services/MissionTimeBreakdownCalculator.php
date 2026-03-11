<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Service;

class MissionTimeBreakdownCalculator
{
    public function breakdown(Carbon $start, Carbon $end, Service $service): array
    {
        if ($end->lessThanOrEqualTo($start)) {
            return $this->empty();
        }

        $cursor = $start->copy();
        $out = $this->empty();

        // Pré-calcul des fériés pour les années couvertes
        $years = [];
        for ($y = $start->year; $y <= $end->year; $y++) $years[] = $y;

        $holidays = [];
        foreach ($years as $y) {
            foreach ($this->frHolidays($y) as $d) $holidays[$d] = true; // "YYYY-MM-DD" => true
        }

        while ($cursor->lessThan($end)) {

            // Prochain breakpoint: minuit, 06:00 ou 21:00
            $next = $this->nextBreakpoint($cursor)->min($end);

            $minutes = $cursor->diffInMinutes($next);
            if ($minutes <= 0) {
                $cursor = $next;
                continue;
            }

            $isNight = $this->isNight($cursor); // la tranche [cursor -> next] est homogène (breakpoints garantissent)
            $dateKey = $cursor->toDateString(); // YYYY-MM-DD
            $isSunday = $cursor->isSunday();
            $isHoliday = isset($holidays[$dateKey]);

            // Choix catégorie
            if ($isSunday && $isHoliday) {
                $key = $isNight ? 'min_sun_hol_night' : 'min_sun_hol_day';
            } elseif ($isHoliday) {
                $key = $isNight ? 'min_hol_night' : 'min_hol_day';
            } elseif ($isSunday) {
                $key = $isNight ? 'min_sun_night' : 'min_sun_day';
            } else {
                $key = $isNight ? 'min_night' : 'min_day';
            }

            $out[$key] += $minutes;
            $out['min_total'] += $minutes;

            $cursor = $next;
        }

        // Montant HT à la minute (tarifs horaires / 60)
        $out['amount_ht'] = $this->computeAmountHt($out, $service);

        return $out;
    }

    private function computeAmountHt(array $m, Service $s): float
    {
        $rate = fn (string $fieldHour) => ((float) ($s->{$fieldHour} ?? 0)) / 60.0;

        $amount =
            $m['min_day']            * $rate('rate_day_hour') +
            $m['min_night']          * $rate('rate_night_hour') +
            $m['min_sun_day']        * $rate('rate_sun_day_hour') +
            $m['min_sun_night']      * $rate('rate_sun_night_hour') +
            $m['min_hol_day']        * $rate('rate_hol_day_hour') +
            $m['min_hol_night']      * $rate('rate_hol_night_hour') +
            $m['min_sun_hol_day']    * $rate('rate_sun_hol_day_hour') +
            $m['min_sun_hol_night']  * $rate('rate_sun_hol_night_hour');

        return round($amount, 2);
    }

    private function empty(): array
    {
        return [
            'min_total' => 0,
            'min_day' => 0, 'min_night' => 0,
            'min_sun_day' => 0, 'min_sun_night' => 0,
            'min_hol_day' => 0, 'min_hol_night' => 0,
            'min_sun_hol_day' => 0, 'min_sun_hol_night' => 0,
            'amount_ht' => 0.0,
        ];
    }

    private function isNight(Carbon $t): bool
    {
        $h = (int) $t->format('H');
        // Nuit = 21:00 -> 06:00
        return ($h >= 21) || ($h < 6);
    }

    private function nextBreakpoint(Carbon $t): Carbon
    {
        $candidates = [];

        // Minuit
        $candidates[] = $t->copy()->addDay()->startOfDay();

        // Prochain 06:00
        $six = $t->copy()->setTime(6, 0, 0);
        if ($six->lessThanOrEqualTo($t)) $six->addDay();
        $candidates[] = $six;

        // Prochain 21:00
        $twentyOne = $t->copy()->setTime(21, 0, 0);
        if ($twentyOne->lessThanOrEqualTo($t)) $twentyOne->addDay();
        $candidates[] = $twentyOne;

        return collect($candidates)->sort()->first();
    }

    /**
     * Jours fériés France (métropole) pour une année.
     * Inclut fixes + mobiles (Pâques, Ascension, Pentecôte).
     */
    private function frHolidays(int $year): array
    {
        // Dates fixes
        $fixed = [
            "$year-01-01", // Jour de l'an
            "$year-05-01", // Fête du travail
            "$year-05-08", // Victoire 1945
            "$year-07-14", // Fête nationale
            "$year-08-15", // Assomption
            "$year-11-01", // Toussaint
            "$year-11-11", // Armistice
            "$year-12-25", // Noël
        ];

        // Calcul Pâques (algorithme PHP via easter_date)
        $easter = $this->easterSunday($year);

        $mobile = [
            $easter->copy()->addDay()->toDateString(),        // Lundi de Pâques
            $easter->copy()->addDays(39)->toDateString(),     // Ascension
            $easter->copy()->addDays(50)->toDateString(),     // Lundi de Pentecôte
        ];

        return array_merge($fixed, $mobile);
    }

    // Compute Easter Sunday (Gregorian) in pure PHP
        private function easterSunday(int $year): Carbon
        {
            $a = $year % 19;
            $b = intdiv($year, 100);
            $c = $year % 100;
            $d = intdiv($b, 4);
            $e = $b % 4;
            $f = intdiv($b + 8, 25);
            $g = intdiv($b - $f + 1, 3);
            $h = (19 * $a + $b - $d - $g + 15) % 30;
            $i = intdiv($c, 4);
            $k = $c % 4;
            $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
            $m = intdiv($a + 11 * $h + 22 * $l, 451);
            $month = intdiv($h + $l - 7 * $m + 114, 31);      // 3=March, 4=April
            $day = (($h + $l - 7 * $m + 114) % 31) + 1;

            return Carbon::create($year, $month, $day)->startOfDay();
        }
}