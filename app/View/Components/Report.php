<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use App\Models\Budget;

class Report extends Component
{
    public $expenses;
    public $budgetBalance;
    /**
     * Create a new component instance.
     */
    public function __construct($expenses)
    {
        $this->expenses = json_decode($expenses);
        $this->budgetBalance = Budget::latest('id')->first()->amount ?? 0;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.report');
    }
}
