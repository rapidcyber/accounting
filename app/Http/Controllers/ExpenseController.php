<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExpensesExport;
use App\Models\Expense;
use App\Models\Budget;

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

    public function print(Request $request){
        $budgetBalance = Budget::latest('date')->first()->amount ?? 0;

        // Get parameters from the request if needed
        $parameters = $request->all();
        // Fetch expenses based on parameters (customize as needed)
        $expenses = Expense::query();

        // Example: filter by date range if provided
        if (!empty($parameters['date_from'])) {
            $expenses->whereBetween('date', [$parameters['date_from'], $parameters['date_to']]);
        }

        $expenses = $expenses->get();

        // Return a view for printing (create resources/views/expenses/print.blade.php)
        return view('expenses.print', [
            'expenses' => $expenses,
            'parameters' => $parameters,
            'budgetBalance' => $budgetBalance,
        ]);
    }
}
