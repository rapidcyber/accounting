<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class BudgetController extends Controller
{
    public function print(Request $request)
    {
        $param = $request->all();
        $budgets = Budget::all();

        if(!empty($param['date_from']) && !empty($param['date_to'])) {
            $budgets = Budget::whereBetween('date', [$param['date_from'], $param['date_to']])->get();
        }

        return view('budgets.print', compact('budgets'));
    }
}
