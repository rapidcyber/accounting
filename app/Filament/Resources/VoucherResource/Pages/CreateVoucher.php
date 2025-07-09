<?php

namespace App\Filament\Resources\VoucherResource\Pages;

use App\Filament\Resources\VoucherResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateVoucher extends CreateRecord
{
    protected static string $resource = VoucherResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateAnotherFormAction()
                ->label('Save & create new')
                ->keyBindings('enter')
                ->color('primary'), // 🔁 Changes "Create & create another"
            Action::make('save')
                ->label('Save')
                ->action(function () {
                    $this->create();
                    // $this->notify('success', 'User saved!');
                    $this->redirect(route('filament.admin.resources.vouchers.index')); // reload create page
                }),
            $this->getCancelFormAction()
                ->label('Cancel'), // 🔁 change from "Cancel" to "Close"
            $this->getCancelFormAction()
                ->label('Close')
                ->url(VoucherResource::getUrl('index')), // 🔁 link to expenses list
        ];
    }
}
