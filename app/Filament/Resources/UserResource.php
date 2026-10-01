<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Role;
use App\Models\User;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 1;

    /** Only admins can see and manage users. */
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
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('email_verified_at')
                    ->hidden()
                    ->default(now()),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    // Leave blank when editing to keep the current password.
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText(fn (string $operation): ?string => $operation === 'edit' ? 'Leave blank to keep the current password.' : null)
                    ->minLength(8)
                    ->maxLength(255),
                Forms\Components\Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->rules([
                        fn (?User $record): Closure => function (string $attribute, $value, Closure $fail) use ($record) {
                            $adminRoleId = Role::admin()?->id;

                            if ($record?->is(auth()->user())
                                && $adminRoleId
                                && ! in_array($adminRoleId, array_map('intval', (array) $value), true)) {
                                $fail('You cannot remove the admin role from your own account.');
                            }
                        },
                    ])
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->unique(Role::class, 'name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('description')
                            ->maxLength(255),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('roles'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->color(fn (string $state): string => $state === Role::ADMIN ? 'danger' : 'primary'),
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
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    // Admins cannot delete their own account.
                    ->hidden(fn (User $record): bool => $record->is(auth()->user())),
            ])
            ->checkIfRecordIsSelectableUsing(fn (User $record): bool => ! $record->is(auth()->user()))
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageUsers::route('/'),
        ];
    }
}
