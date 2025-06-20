<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Filament\Resources\ExpenseResource\RelationManagers;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Exports\ExpensesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Exp;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->required(),
                Forms\Components\TextInput::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                        // When the quantity changes, get the current amount (or default to 0)
                        $amount = (float) ($get('amount') ?? 0);
                        // Multiply the two values and update total_amount
                        $set('total_amount', ((float) $state) * $amount);
                    })
                    ->required(),
                Forms\Components\TextInput::make('unit')
                    ->label('Unit')
                    ->helperText('e.g., pcs, kg, liters')
                    ->maxLength(50)
                    ->nullable(),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Max 255 characters.'),
                Forms\Components\TextInput::make('amount')
                    ->label('Amount')
                    ->numeric()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                        // When the amount changes, get the current quantity (or default to 0)
                        $quantity = (float) ($get('quantity') ?? 0);
                        // Multiply the two values and update total_amount
                        $set('total_amount', ((float) $state) * $quantity);
                    })
                    ->required(),

                Forms\Components\TextInput::make('total_amount')
                    ->debounce(2000)
                    ->label('Total Amount')
                    ->numeric()
                    // Disable the field so it helps act as read-only,
                    // meaning users can’t edit it directly.
                    ->disabled(),

                Forms\Components\Select::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'credit_card' => 'Credit Card',
                        'debit_card' => 'Debit Card',
                        'bank_transfer' => 'Bank Transfer',
                        'e-wallet' => 'E-Wallet',
                    ])
                    ->default('cash'),
                Forms\Components\FileUpload::make('receipt_image')
                    ->label('Proof of payment / Receipt Image')
                    ->image()
                    ->nullable(),
                Forms\Components\Hidden::make('created_by')
                    ->default(fn () => auth()->id()),
                Forms\Components\Hidden::make('updated_by')
                    ->default(fn () => auth()->id())
            ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('PHP', true)
                    ->sortable(),
                TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                // Tables\Columns\TextColumn::make('receipt_image')
                //     ->label('Receipt Image')
                //     // ->url(fn (Expense $record) => $record->receipt_image ? asset('storage/' . $record->receipt_image) : null)
                //     ->formatStateUsing(fn ($state, Expense $record) => $record->receipt_image
                //         ? '<a href="' . asset('storage/' . $record->receipt_image) . '" target="_blank" rel="noopener noreferrer"><img src="' . asset('storage/' . $record->receipt_image) . '" alt="Receipt" style="max-height:40px;max-width:60px;object-fit:cover;" /></a>'
                //         : ''
                //     )
                //     ->html()
                //     ->openUrlInNewTab(),

            ])
            ->headerActions([
                // Grand Total display
                Action::make('grandTotal')
                    ->label(function () {
                        $total = Expense::query()->sum('total_amount');
                        return 'Grand Total: ₱' . number_format($total, 2);
                    })
                    ->disabled()
                    ->color('gray')
                    ->icon('heroicon-o-calculator'),
                // Action to generate report
                Action::make('generateReport')
                    ->label('Generate Report')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->action(function ($livewire) {
                        // Handle the report generation logic here
                        self::handleReportGeneration($livewire);
                    })
                    // ->url(route('reports.posts')) // replace with your route
                    // ->openUrlInNewTab(),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('period')
                    ->label('Period')
                    ->options([
                        'weekly' => 'This Week',
                        'monthly' => 'This Month',
                        'quarterly' => 'This Quarter',
                        'annually' => 'This Year',
                    ])
                    ->query(function (Builder $query, $data) {
                        $period = $data['value'] ?? null;

                        if ($period) {
                            switch ($period) {
                                case 'weekly':
                                    return $query->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]);
                                case 'monthly':
                                    return $query->whereMonth('date', now()->month);
                                case 'quarterly':
                                    return $query->whereQuarter('date', now()->quarter);
                                case 'annually':
                                    return $query->whereYear('date', now()->year);
                            }
                        }
                        return $query;
                    }),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('To'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['date_from'], fn ($q) => $q->whereDate('date', '>=', $data['date_from']))
                            ->when($data['date_to'], fn ($q) => $q->whereDate('date', '<=', $data['date_to']));
                    }),

            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
    public static function handleReportGeneration($livewire)
    {
        $period = $livewire->getTable()->getFilter('period')->getState();
        $dateRange = $livewire->getTable()->getFilter('date_range')->getState();
        $params = [
            'type' => 'period',
            'period' => $period['value'] ?? 'All',
        ];

        if($dateRange['date_from'] && $dateRange['date_to']) {
            $params = [
                'type' => 'date_range',
                'date_from' => $dateRange['date_from'],
                'date_to' => $dateRange['date_to'],
            ];
        }

        // return Excel::download(new ExpensesExport($params), 'report_' . now()->format('Y-m-d') . '.xlsx');
        // Redirect to a dedicated download route with parameters
        $query = http_build_query($params);

        return redirect()->to(route('export.expenses', [], false) . '?' . $query);
    }

}
