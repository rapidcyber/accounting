<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Budget;
use App\Models\Expense;
use Illuminate\Support\HtmlString;
use Illuminate\Contracts\Support\Htmlable;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    public function getTitle(): string|Htmlable
    {

        $budgetBalance = Budget::balance();

        return new HtmlString('<h1 class="fi-header-heading text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">Create Expense</h1><p>Budget Balance: <strong style="color:red">' . number_format($budgetBalance, 2) . '</strong></p>');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateAnotherFormAction()
                ->label('Save & create new')
                ->keyBindings('enter')
                ->color('primary'), // 🔁 Changes "Create & create another"
            $this->getCreateFormAction()
                ->label('Save')
                ->successRedirectUrl(ExpenseResource::getUrl('index')), // 🔁 Redirect to list after save
            $this->getCancelFormAction()
                ->label('Cancel'), // 🔁 change from "Cancel" to "Close"
            $this->getCancelFormAction()
                ->label('Close')
                ->url(ExpenseResource::getUrl('index')), // 🔁 link to expenses list
        ];
    }

    protected function beforeCreate(): void
    {
        // Check the full total (amount x quantity), not just the unit amount.
        $total = Expense::computeTotal($this->data);
        $balance = Budget::balance();

        if ($total > $balance) {
            \Filament\Notifications\Notification::make()
                ->title('Insufficient budget!')
                ->danger()
                ->body('This expense (' . number_format($total, 2) . ') is more than the budget balance (' . number_format($balance, 2) . ').')
                ->send();

            $this->halt();
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        return $data;
    }

    // No afterCreate budget bookkeeping: the balance is calculated from the
    // expenses themselves, so a new expense is deducted automatically.
}
