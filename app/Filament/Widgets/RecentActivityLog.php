<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Request;
use Livewire\WithPagination;

class RecentActivityLog extends Widget
{
    use WithPagination;
    protected static string $view = 'filament.widgets.recent-activity-log';
    protected int|string|array $columnSpan = 'full';

    public function getPaginatedRecords(): LengthAwarePaginator
    {
        return Activity::with('causer')
            ->latest()
            ->paginate(10);
    }
}
