<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * One place that turns the expense table filters (Period + From/To) into a
 * date range, so the table, Grand Total, preview, print and Excel download
 * always cover exactly the same expenses.
 */
class ExpensePeriod
{
    public const OPTIONS = [
        'weekly' => 'This Week',
        'monthly' => 'This Month',
        'quarterly' => 'This Quarter',
        'annually' => 'This Year',
    ];

    /**
     * @return array{date_from: ?string, date_to: ?string}
     */
    public static function range(?string $period, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $now = now();

        [$from, $to] = match ($period) {
            'weekly' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'monthly' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'quarterly' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'annually', 'yearly' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => [null, null],
        };

        $from = $from?->toDateString();
        $to = $to?->toDateString();

        // The table applies both filters together, so narrow the period by From/To too.
        if ($dateFrom) {
            $from = $from ? max($from, substr($dateFrom, 0, 10)) : substr($dateFrom, 0, 10);
        }
        if ($dateTo) {
            $to = $to ? min($to, substr($dateTo, 0, 10)) : substr($dateTo, 0, 10);
        }

        return ['date_from' => $from, 'date_to' => $to];
    }

    /**
     * Range from the Filament table's current filter state.
     */
    public static function fromTable($livewire): array
    {
        $table = $livewire->getTable();
        $period = $table->getFilter('period')?->getState()['value'] ?? null;
        $dates = $table->getFilter('date_range')?->getState() ?? [];

        return static::range($period, $dates['date_from'] ?? null, $dates['date_to'] ?? null);
    }

    public static function apply(Builder $query, array $range): Builder
    {
        return $query
            ->when($range['date_from'] ?? null, fn (Builder $q, $from) => $q->whereDate('date', '>=', $from))
            ->when($range['date_to'] ?? null, fn (Builder $q, $to) => $q->whereDate('date', '<=', $to));
    }
}
