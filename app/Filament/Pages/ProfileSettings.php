<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

use Filament\Forms\Contracts\HasForms;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\Facades\Hash;
// use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;


class ProfileSettings extends Page implements HasForms
{
    use InteractsWithForms;


    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.profile-settings';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = []; // 👈 ADD THIS

    public function mount(): void
    {
        $this->data = [
            'name' => auth()->user()->name,
            'email' => auth()->user()->email,
        ];
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->statePath('data.name')
                ->required(),
            Forms\Components\TextInput::make('email')
                ->email()
                ->statePath('data.email')
                ->required(),
            Forms\Components\TextInput::make('password')
                ->statePath('data.password')
                ->label('New Password')
                ->password()
                ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                ->dehydrated(fn ($state) => filled($state)),
        ];
    }

    public function save(): void
    {

        $user = auth()->user();
        $data = $this->form->getState()['data'];

        // Don't update empty password
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        Notification::make()
            ->title('Profile updated successfully.')
            ->success()
            ->send();
    }

    protected function getFormModel(): \App\Models\User
    {
        return auth()->user();
    }

}
