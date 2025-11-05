<?php

namespace App\Observers;

use App\Models\Budget;
use Illuminate\Support\Facades\Auth;

class BudgetObserver
{
    /**
     * Handle the Budget "created" event.
     */
    public function created(Budget $budget): void
    {
        activity()
            ->performedOn($budget)
            ->causedBy(Auth::user())
            ->log('Created a new budget');
    }

    /**
     * Handle the Budget "updated" event.
     */
    public function updated(Budget $budget): void
    {
        activity()
            ->performedOn($budget)
            ->causedBy(Auth::user())
            ->log('Updated an budget');
    }

    /**
     * Handle the Budget "deleted" event.
     */
    public function deleted(Budget $budget): void
    {
        activity()
            ->performedOn($budget)
            ->causedBy(Auth::user())
            ->log('Deleted an budget');
    }

    /**
     * Handle the Budget "restored" event.
     */
    public function restored(Budget $budget): void
    {
        activity()
            ->performedOn($budget)
            ->causedBy(Auth::user())
            ->log('Restored an budget');
    }

    /**
     * Handle the Budget "force deleted" event.
     */
    public function forceDeleted(Budget $budget): void
    {
        //
    }
}
