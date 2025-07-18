<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Budget;
use Illuminate\Support\HtmlString;
use Illuminate\Contracts\Support\Htmlable;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    public function getTitle(): string|Htmlable
    {

        $budgetBalance = Budget::latest()->first()->amount ?? 0;

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
        $latestBudget = Budget::latest()->first();
        $expenseAmount = $this->data['amount'] ?? 0;

        if (!$latestBudget || $latestBudget->amount < $expenseAmount) {
            \Filament\Notifications\Notification::make()
                ->title('Insufficient budget!')
                ->danger()
                ->body('The budget is not enough to cover this expense.')
                ->send();

            $this->halt();
        }
    }

    protected function afterCreate(): void
    {
        // You can use a notification instead of dd() for better UX
        $latestBudget = Budget::latest()->first();
        if ($latestBudget) {

            $newBudgetAmount = $latestBudget->amount - ($this->data['total_amount'] ?? 0);

            $newBudget = new Budget(
                [
                    'amount' => $newBudgetAmount,
                    'date' => now(),
                    'description' => 'Budget updated after expense creation'
                ]
            );

            if ($newBudget->save()) {
                $newBudget->expenses()->attach($this->record->id);
                 \Filament\Notifications\Notification::make()
                    ->title('Expense created successfully!')
                    ->success()
                    ->send();
                \Filament\Notifications\Notification::make()
                    ->title('Budget updated successfully!')
                    ->success()
                    ->body('New budget created with updated amount.')
                    ->send();

            } else {
                \Filament\Notifications\Notification::make()
                    ->title('Budget update failed!')
                    ->danger()
                    ->body('There was an issue updating the budget.')
                    ->send();
            }
        }



    }
}
