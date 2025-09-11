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
        $param = $request->all();
        $budgets = Budget::all();
        $dateFrom = Carbon::parse($param['date_from']);
        $dateTo = Carbon::parse($param['date_to']);


        if(!empty($param['date_from']) && !empty($param['date_to'])) {
            $budgets = Budget::whereDate('date', '>=', $dateFrom)->whereDate('date', '<=', $dateTo)->orderBy('date', 'asc')->get();
        }

        return view('budgets.print', compact('budgets'));
    }
}
