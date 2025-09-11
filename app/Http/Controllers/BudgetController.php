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
        $dateTo = Carbon::parse($param['date_to'])->addDay();


        if(!empty($param['date_from']) && !empty($param['date_to'])) {
            $budgets = Budget::whereBetween('date', [Carbon::parse($param['date_from']), $dateTo])->orderBy('date', 'asc')->get();
        }

        return view('budgets.print', compact('budgets'));
    }
}
