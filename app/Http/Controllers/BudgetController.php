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

        // A period covers what was entered (recorded) in it, listed by date.
        // Expenses are often entered days late and paid from money added on
        // the day they were entered, so this keeps each period's balance real.
        $entries = LedgerEntry::query()
            ->when($from, fn ($q) => $q->where('recorded_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('recorded_at', '<', $to->copy()->addDay()))
            ->orderBy('position')
            ->get();

        // Balance at the start of the period: everything entered before it.
        $broughtForward = $from
            ? (float) LedgerEntry::query()->where('recorded_at', '<', $from)->sum(DB::raw('money_in - money_out'))
            : 0.0;

        $totalAdded = (float) $entries->sum('money_in');
        $totalSpent = (float) $entries->sum('money_out');
        $endingBalance = round($broughtForward + $totalAdded - $totalSpent, 2);

        $dateFrom = $from ?? ($entries->isNotEmpty() ? Carbon::parse($entries->min('recorded_at')) : null);
        $dateTo = $to ?? ($entries->isNotEmpty() ? Carbon::parse($entries->max('recorded_at')) : null);

        return view('budgets.print', compact('entries', 'dateFrom', 'dateTo', 'broughtForward', 'totalAdded', 'totalSpent', 'endingBalance'));
    }
}
