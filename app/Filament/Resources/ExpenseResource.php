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
use App\Models\Budget;
use App\Support\ExpensePeriod;
// use App\Exports\ExpensesExport;
// use Maatwebsite\Excel\Facades\Excel;
// use Illuminate\Database\Eloquent\SoftDeletingScope;
// use PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Exp;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 1;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->label('Date of expense')
                    ->required()
                    ->default(now())
                    ->minDate('2025-01-01')
                    ->maxDate(fn () => now()->endOfDay()) // an expense cannot be in the future
                    ->live()
                    ->helperText(fn ($state) => static::describeDate($state)),
                Forms\Components\TextInput::make('quantity')
                    ->label('Quantity')
                    ->numeric()           // Ensures only numbers
                    ->step(1)             // Disables decimal input
                    ->inputMode('numeric') // Shows numeric keyboard on mobile
                    ->rules(['integer', 'min:1'])
                    ->lazy()
                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                        // When the quantity changes, get the current amount (or default to 0)
                        $amount = $get('amount') ?? 0;
                        // Multiply the two values and update total_amount
                        $set('total_amount', $state * $amount);
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
                    ->lazy()
                    // ->reactive()
                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                        // When the amount changes, get the current quantity (or default to 0)
                        $quantity = $get('quantity') ?? 0;
                        // Multiply the two values and update total_amount
                        $set('total_amount', ((float) $state) * $quantity);
                    })
                    ->minValue(0.01)
                    ->required(),

                Forms\Components\TextInput::make('total_amount')
                    ->label('Total Amount')
                    ->numeric()
                    // Disable the field so it helps act as read-only,
                    // meaning users can’t edit it directly.
                    ->disabled()
                    ->dehydrated(false),

                Forms\Components\Select::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'e-wallet' => 'E-Wallet',
                        'check' => 'Check',
                        'bank_transfer' => 'Bank Transfer',
                        'debit_card' => 'Debit Card',
                        'credit_card' => 'Credit Card',
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
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->description)
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state) => number_format($state, 2))
                    ->sortable(),
                TextColumn::make('date')
                    ->label('Date')
                    ->date('M d, Y')
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('Quantity')
                    ->formatStateUsing(function ($state) {
                        // If the value is a whole number, show without decimals
                        return (is_numeric($state) && floor($state) == $state)
                            ? number_format($state, 0)
                            : $state;
                    })
                    ->alignCenter(),
                TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => number_format($state, 2)) // <-- key part
                    ->sortable(),
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
                //Budget Balance display
                Action::make('budgetBalance')
                    ->label(function () {
                        return 'Budget Balance: '. number_format(Budget::balance(), 2);
                    })
                    ->disabled()
                    ->icon('heroicon-o-wallet')
                    ->color('gray'),
                // Grand Total display
                Action::make('grandTotal')
                    ->label(function ($livewire) {
                        $range = ExpensePeriod::fromTable($livewire);
                        $total = ExpensePeriod::apply(Expense::query(), $range)->sum('total_amount');

                        return 'Grand Total: ' . number_format($total, 2);
                    })
                    ->disabled()
                    ->color('gray')
                    ->icon('heroicon-o-calculator'),

                Action::make('viewDetails')
                    ->label('PREVIEW AND DOWNLOAD')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->modalHeading('Epenses Report')
                    ->modalSubmitAction(false) // no submit button
                    ->modalFooterActions([
                        Action::make('print')
                            ->label('PRINT')
                            ->icon('heroicon-o-printer')
                            ->color('primary')
                            ->url(function ($livewire) {
                                $query = http_build_query(ExpensePeriod::fromTable($livewire));

                                return route('expenses.print', [], false) . '?' . $query;
                            })
                            ->openUrlInNewTab(),
                        Action::make('generateReport')
                            ->label('DOWNLOAD')
                            ->icon('heroicon-o-document-text')
                            ->color('success')
                            ->action(function ($livewire) {
                                // Handle the report generation logic here
                                self::handleReportGeneration($livewire);
                            }),

                    ])
                    ->modalCancelActionLabel('Close')

                    ->modalContent(function ($livewire) {
                        $range = ExpensePeriod::fromTable($livewire);
                        $expenses = ExpensePeriod::apply(Expense::query(), $range)->orderBy('date')->orderBy('id')->get();

                        return view('components.report', ['expenses' => $expenses, 'budgetBalance' => Budget::balance()]);
                    }),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('period')
                    ->label('Period')
                    ->options(ExpensePeriod::OPTIONS)
                    ->query(fn (Builder $query, $data) => ExpensePeriod::apply($query, ExpensePeriod::range($data['value'] ?? null))),
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
                // Deleting an expense automatically returns its amount to the balance,
                // because the balance is calculated from the remaining expenses.
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->paginated([10, 25, 50, 100])
            ->deferLoading()
            ->defaultSort(fn (Builder $query) => $query->orderByDesc('date')->orderByDesc('id'));
    }

    /**
     * Spell out the chosen date so a wrong day, month or year stands out.
     */
    public static function describeDate($state): string
    {
        if (blank($state)) {
            return 'Pick the date from the calendar.';
        }

        $date = \Illuminate\Support\Carbon::parse($state)->startOfDay();
        $days = (int) $date->diffInDays(now()->startOfDay(), false);

        $when = match (true) {
            $days === 0 => 'today',
            $days === 1 => 'yesterday',
            $days > 1 => $days . ' days ago',
            default => 'in the future',
        };

        $text = $date->format('l, F j, Y') . ' (' . $when . ')';

        return $days > 31 ? $text . ' - more than a month ago, please double-check.' : $text;
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
            'list' => Pages\ListExpenses::route('/list'),
            'create' => Pages\CreateExpense::route('/create'),
            'index' => Pages\ListExpenses::route('/'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }

    public static function getNavigationUrl(): string
    {
        return static::getUrl('create');
    }


    public static function handleReportGeneration($livewire)
    {
        $query = http_build_query(ExpensePeriod::fromTable($livewire));

        return redirect()->to(route('export.expenses', [], false) . '?' . $query);
    }

    public static function handleReportPrint($livewire)
    {
        $query = http_build_query(ExpensePeriod::fromTable($livewire));

        return redirect()->to(route('expenses.print', [], false) . '?' . $query);
    }
}
