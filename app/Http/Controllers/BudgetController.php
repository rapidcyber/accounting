<?php

namespace App\Http\Controllers;

use App\Models\LedgerEntry;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class BudgetController extends Controller
{
    public function print(Request $request)
    {
        $entries = LedgerEntry::query()
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('entry_date', '>=', Carbon::parse($request->date_from)))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('entry_date', '<=', Carbon::parse($request->date_to)))
            ->orderBy('position')
            ->get();

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : $entries->min('entry_date');
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : $entries->max('entry_date');

        return view('budgets.print', compact('entries', 'dateFrom', 'dateTo'));
    }
}
