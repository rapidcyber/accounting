<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Request;

class RecentActivityLog extends Widget
{
    protected static string $view = 'filament.widgets.recent-activity-log';
    protected int|string|array $columnSpan = 'full';

    public function getPaginatedRecords(): LengthAwarePaginator
    {
        $page = Request::input('page', 1);
        $perPage = 10;

        $query = Activity::with('causer')->latest();
        $total = $query->count();
        $results = $query->forPage($page, $perPage)->get();

        return new LengthAwarePaginator($results, $total, $perPage, $page, [
            'path' => Request::url(),
            'query' => Request::query(),
        ]);
    }
}
