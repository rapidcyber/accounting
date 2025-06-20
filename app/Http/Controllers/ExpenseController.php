<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExpensesExport;
use App\Models\Expense;

class ExpenseController extends Controller
{
    public function exportToExcel(Request $request)
    {
        // Decode the parameters from the request
        $parameters = $request->all();

        // Validate the parameters if necessary
        if (!is_array($parameters)) {
            return response()->json(['error' => 'Invalid parameters'], 400);
        }

        return Excel::download(
            new ExpensesExport($parameters),
            'expenses_'.now()->format('Y_m_d_H_i_s').'.xlsx'
        );
    }
}
