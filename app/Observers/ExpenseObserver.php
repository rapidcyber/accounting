<?php

namespace App\Observers;

use App\Models\Expense;
use Illuminate\Support\Facades\Auth;

class ExpenseObserver
{
    /**
     * Handle the Expense "created" event.
     */
    public function created(Expense $expense): void
    {
        activity()
            ->performedOn($expense)
            ->causedBy(Auth::user())
            ->log('Created a new expense');
    }

    /**
     * Handle the Expense "updated" event.
     */
    public function updated(Expense $expense): void
    {
        activity()
            ->performedOn($expense)
            ->causedBy(Auth::user())
            ->log('Updated an expense');
    }

    /**
     * Handle the Expense "deleted" event.
     */
    public function deleted(Expense $expense): void
    {
        activity()
            ->performedOn($expense)
            ->causedBy(Auth::user())
            ->log('Deleted an expense');
    }

    /**
     * Handle the Expense "restored" event.
     */
    public function restored(Expense $expense): void
    {
        activity()
            ->performedOn($expense)
            ->causedBy(Auth::user())
            ->log('Restored an expense');
    }

    /**
     * Handle the Expense "force deleted" event.
     */
    public function forceDeleted(Expense $expense): void
    {
        activity()
            ->performedOn($expense)
            ->causedBy(Auth::user())
            ->log('Restored an expense');
    }
}
