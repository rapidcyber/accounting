<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class BudgetController extends Controller
{
    public function print(Request $request)
    {
        $budgets = Budget::query()
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('date', '>=', Carbon::parse($request->date_from)))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('date', '<=', Carbon::parse($request->date_to)))
            ->orderBy('date')
            ->get();

        return view('budgets.print', compact('budgets'));
    }
}
