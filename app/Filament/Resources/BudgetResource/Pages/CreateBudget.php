<?php

namespace App\Filament\Resources\BudgetResource\Pages;

use App\Filament\Resources\BudgetResource;
use Filament\Actions;
use App\Models\Budget;
use Filament\Resources\Pages\CreateRecord;

class CreateBudget extends CreateRecord
{
    protected static string $resource = BudgetResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateAnotherFormAction()
                ->label('Save & create new')
                ->keyBindings('enter')
                ->color('primary'), // 🔁 Changes "Create & create another"
            $this->getCreateFormAction()
                ->label('Save')
                ->successRedirectUrl(BudgetResource::getUrl('index')), // 🔁 Redirect to list after save
            $this->getCancelFormAction()
                ->label('Cancel'), // 🔁 change from "Cancel" to "Close"
            $this->getCancelFormAction()
                ->label('Close')
                ->url(BudgetResource::getUrl('index')), // 🔁 link to expenses list
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $lastBudget = Budget::latest('date')->first();
        if ($lastBudget) {
            $data['amount'] += $lastBudget->amount;
        }
        return $data;
    }
}
