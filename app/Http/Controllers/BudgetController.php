<?php

namespace App\Http\Controllers;

use App\Models\LedgerEntry;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BudgetController extends Controller
{
    public function print(Request $request)
    {
        $from = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : null;
        $to = $request->filled('date_to') ? Carbon::parse($request->date_to)->startOfDay() : null;

        // Only rows dated inside the period, listed by date.
        $entries = LedgerEntry::query()
            ->when($from, fn ($q) => $q->whereDate('entry_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('entry_date', '<=', $to))
            ->orderBy('position')
            ->get();

        // Balance at the start of the first day: everything dated before it.
        $broughtForward = $from
            ? (float) LedgerEntry::query()->whereDate('entry_date', '<', $from)->sum(DB::raw('money_in - money_out'))
            : 0.0;

        $totalAdded = (float) $entries->sum('money_in');
        $totalSpent = (float) $entries->sum('money_out');
        $endingBalance = round($broughtForward + $totalAdded - $totalSpent, 2);

        $dateFrom = $from ?? $entries->min('entry_date');
        $dateTo = $to ?? $entries->max('entry_date');

        return view('budgets.print', compact('entries', 'dateFrom', 'dateTo', 'broughtForward', 'totalAdded', 'totalSpent', 'endingBalance'));
    }
}
