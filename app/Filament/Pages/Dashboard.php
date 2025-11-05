<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\RecentActivityLog;
use App\Filament\Resources\ExpenseResource\Widgets\StatsOverview;

class Dashboard extends BaseDashboard
{
    protected function getHeaderWidgets(): array
    {
        return [
            StatsOverview::class,
        ];
    }

    // protected function getFooterWidgets(): array
    // {
    //     return [
    //         RecentActivityLog::class,
    //     ];
    // }
}

