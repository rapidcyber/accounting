<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use App\Models\Expense;
use App\Observers\ExpenseObserver;
use App\Models\Budget;
use App\Observers\BudgetObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::unguard();
        Expense::observe(ExpenseObserver::class);
        Budget::observe(BudgetObserver::class);
    }
}
