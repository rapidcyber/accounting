<?php

namespace App\Filament\Resources\ExpenseResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Expense;
use App\Models\Voucher;
use App\Models\Budget;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Expenses Grand Total', '₱' . number_format(Expense::sum('total_amount'), 2)),
            Stat::make('Created Vouchers', Voucher::all()->count()),
            Stat::make('Cash on Hand','₱' . number_format(Budget::latest()->first()->amount, 2)),
        ];
    }
}
