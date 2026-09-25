<?php

namespace App\Filament\Pages;

use App\Filament\Resources\BudgetResource;
use App\Filament\Resources\ExpenseResource;
use App\Models\Budget;
use App\Models\LedgerEntry;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Money added and money spent in one list, sorted by date.
 * Built live from budgets and expenses, so it always matches them.
 */
class BudgetHistory extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Budget History';

    protected static ?string $title = 'Budget History';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.budget-history';

    public function table(Table $table): Table
    {
        return $table
            ->query(LedgerEntry::query())
            ->columns([
                TextColumn::make('entry_date')
                    ->label('Date')
                    ->date('M d, Y')
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('position', $direction)),
                TextColumn::make('recorded_at')
                    ->label('Entered')
                    ->date('M d, Y')
                    ->toggleable(),
                TextColumn::make('entry_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'budget' ? 'Budget added' : 'Expense')
                    ->color(fn (string $state) => $state === 'budget' ? 'success' : 'gray'),
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->description)
                    ->searchable(),
                TextColumn::make('money_in')
                    ->label('Added')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state) => (float) $state ? number_format($state, 2) : ''),
                TextColumn::make('money_out')
                    ->label('Spent')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state) => (float) $state ? number_format($state, 2) : ''),
            ])
            ->defaultSort('position', 'desc')
            ->filters([
                SelectFilter::make('entry_type')
                    ->label('Type')
                    ->options(['budget' => 'Budget added', 'expense' => 'Expense']),
                // Same rule as the print: a period covers what was entered in it.
                Filter::make('date')
                    ->form([
                        DatePicker::make('from')->label('Entered from'),
                        DatePicker::make('to')->label('Entered to'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $d) => $q->whereDate('recorded_at', '>=', $d))
                        ->when($data['to'] ?? null, fn (Builder $q, $d) => $q->whereDate('recorded_at', '<=', $d))),
            ])
            ->headerActions([
                Action::make('cashOnHand')
                    ->label(fn () => 'Cash on Hand: ' . number_format(Budget::balance(), 2))
                    ->disabled()
                    ->icon('heroicon-o-wallet')
                    ->color('gray'),
                Action::make('addBudget')
                    ->label('Add Budget')
                    ->icon('heroicon-o-plus')
                    ->url(BudgetResource::getUrl('create')),
                Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(function ($livewire) {
                        $date = $livewire->getTable()->getFilter('date')->getState();

                        return route('budgets.print', [], false) . '?' . http_build_query([
                            'date_from' => $date['from'] ?? null,
                            'date_to' => $date['to'] ?? null,
                        ]);
                    })
                    ->openUrlInNewTab(),
            ])
            ->recordUrl(fn (LedgerEntry $record) => $record->entry_type === 'budget'
                ? BudgetResource::getUrl('edit', ['record' => $record->source_id])
                : ExpenseResource::getUrl('edit', ['record' => $record->source_id]))
            ->paginated([25, 50, 100]);
    }
}
