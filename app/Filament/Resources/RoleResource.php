<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 2;

    /** Only admins can see and manage roles. */
    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    // The built-in admin role keeps its name so access checks keep working.
                    ->disabled(fn (?Role $record): bool => (bool) $record?->isAdmin()),
                Forms\Components\TextInput::make('description')
                    ->maxLength(255),
                Forms\Components\Select::make('users')
                    ->relationship('users', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->rules([
                        fn (?Role $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($record) {
                            if ($record?->isAdmin()
                                && ! in_array(auth()->id(), array_map('intval', (array) $value), true)) {
                                $fail('You cannot remove yourself from the admin role.');
                            }
                        },
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->badge()
                    ->color(fn (string $state): string => $state === Role::ADMIN ? 'danger' : 'primary')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn (Role $record): bool => $record->isAdmin()),
            ])
            ->checkIfRecordIsSelectableUsing(fn (Role $record): bool => ! $record->isAdmin())
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->using(fn (Collection $records) => $records->reject->isAdmin()->each->delete()),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageRoles::route('/'),
        ];
    }
}
