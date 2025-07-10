<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VoucherResource\Pages;
use App\Filament\Resources\VoucherResource\RelationManagers;
use App\Models\Voucher;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\Action as BaseAction;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select;


class VoucherResource extends Resource
{
    protected static ?string $model = Voucher::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';
    protected static ?int $navigationSort = 2;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('expenses')
                    ->label('Expenses')
                    ->relationship('expenses', 'description')
                    ->placeholder('Select expenses')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label('To')
                    ->placeholder('Name of recipient')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('date')
                    ->label('Date')
                    ->default(now())
                    ->required(),
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
                Tables\Columns\TextColumn::make('Description')
                    ->label('Expenses')
                    ->getStateUsing(function ($record) {
                        $expenses = [];

                        foreach($record->expenses as $item){

                            $expenses[] = $item->quantity . ' '. $item->unit . ' '. $item->description;
                        }

                        return implode(', ', $expenses);
                    })
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->getStateUsing(function ($record) {
                        return $record->expenses->sum('total_amount');
                    })
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make('viewDetails')
                    ->label('View Details')
                    ->form([])
                    ->modalSubmitAction(false)
                    ->modalContent(function($record){
                        return view('components.voucher', ['voucher' => $record]);
                    })
                    ->modalFooterActions([
                        Action::make('print')
                            ->label('PRINT')
                            ->icon('heroicon-o-printer')
                            ->color('primary')
                            ->url(function ($record) {
                                session()->put('print_user_ids', [$record->id]);
                                return route('vouchers.print');
                            })
                            ->openUrlInNewTab(),
                        BaseAction::make('close')
                            ->label('Close')
                            ->color('gray')
                            ->close(),
                    ]),
                Tables\Actions\EditAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
                BulkAction::make('print')
                ->label('Print')
                ->icon('heroicon-m-printer')
                ->requiresConfirmation()
                ->action(function (Collection $records) {
                    // Store selected record IDs in the session
                    session()->put('print_user_ids', $records->pluck('id')->toArray());
                })
                ->after(function () {
                    // Redirect to print preview route
                    return redirect()->route('vouchers.print');
                }),
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
            'index' => Pages\ListVouchers::route('/'),
            'create' => Pages\CreateVoucher::route('/create'),
            'edit' => Pages\EditVoucher::route('/{record}/edit'),
        ];
    }

    // If you want to include soft deleted records, use withTrashed():
    // public static function getEloquentQuery(): Builder
    // {
    //     return parent::getEloquentQuery()
    //         ->withTrashed();
    // }

    // Or, if you want to exclude soft deleted records, simply do not override getEloquentQuery().
}
