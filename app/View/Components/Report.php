<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Report extends Component
{
    public $expenses;
    /**
     * Create a new component instance.
     */
    public function __construct($expenses)
    {
        $this->expenses = json_decode($expenses);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.report');
    }
}
