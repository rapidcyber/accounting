<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\Budget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
class EditExpense extends EditRecord
{
    protected static string $resource = ExpenseResource::class;

    public function getTitle(): string|Htmlable
    {
        // dd($this->record->id);
        $budgetBalance = $this->record->budgets->first()->amount ?? 0;

        return new HtmlString('<h1 class="fi-header-heading text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">Edit Expense</h1><p>Budget Balance: <strong style="color:red">' . number_format($budgetBalance, 2) . '</strong></p>');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $lastBudget = $this->record->budgets->first()->amount ?? 0;
        $beforeBudget = $lastBudget + $this->record->total_amount;
        $expenseAmount = $this->data['total_amount'] ?? 0;
        if ($lastBudget) {
            if ($beforeBudget < $expenseAmount) {
                \Filament\Notifications\Notification::make()
                    ->title('Insufficient budget!')
                    ->danger()
                    ->body('The budget is not enough to cover this expense.')
                    ->send();

                $this->halt();
            }

            $budget = $this->record->budgets->first();
            $budget->amount = $beforeBudget - $expenseAmount;
            if ($budget->save()) {
                \Filament\Notifications\Notification::make()
                    ->title('Budget Updated!')
                    ->success()
                    ->body('The budget has been updated successfully.')
                    ->send();
            } else {
                \Filament\Notifications\Notification::make()
                    ->title('Budget Update Failed!')
                    ->danger()
                    ->body('There was an error updating the budget.')
                    ->send();
            }
        }
    }

    // protected function beforeUpdate(): void
    // {
    //     $lastBudget = $this->record->budgets->first()->amount ?? 0;
    //     $beforeBudget = $lastBudget + $this->record->amount;
    //     $expenseAmount = $this->data['amount'] ?? 0;
    //     if($lastBudget) {
    //         if ($beforeBudget < $expenseAmount) {
    //             \Filament\Notifications\Notification::make()
    //                 ->title('Insufficient budget!')
    //                 ->danger()
    //                 ->body('The budget is not enough to cover this expense.')
    //                 ->send();

    //             $this->halt();
    //         }

    //         $bugdet = $this->record->budgets->first();
    //         $bugdet->amount = $beforeBudget - $expenseAmount;
    //         if($bugdet->save()) {
    //             \Filament\Notifications\Notification::make()
    //                 ->title('Budget Updated!')
    //                 ->success()
    //                 ->body('The budget has been updated successfully.')
    //                 ->send();
    //         } else {
    //             \Filament\Notifications\Notification::make()
    //                 ->title('Budget Update Failed!')
    //                 ->danger()
    //                 ->body('There was an error updating the budget.')
    //                 ->send();
    //         }
    //     }
    // }
}
