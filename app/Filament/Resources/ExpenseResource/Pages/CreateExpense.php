<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateAnotherFormAction()
                ->label('Save & create new')
                ->keyBindings('enter')
                ->color('primary'), // 🔁 Changes "Create & create another"
            $this->getCreateFormAction()
                ->label('Save')
                ->successRedirectUrl(ExpenseResource::getUrl('list')), // 🔁 Redirect to list after save
            $this->getCancelFormAction()
                ->label('Cancel'), // 🔁 change from "Cancel" to "Close"
            $this->getCancelFormAction()
                ->label('Close')
                ->url(ExpenseResource::getUrl('list')), // 🔁 link to expenses list
        ];
    }
}
