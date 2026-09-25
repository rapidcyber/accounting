<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\Budget;
use App\Models\Expense;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
class EditExpense extends EditRecord
{
    protected static string $resource = ExpenseResource::class;

    public function getTitle(): string|Htmlable
    {
        $budgetBalance = Budget::balance();

        return new HtmlString('<h1 class="fi-header-heading text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">Edit Expense</h1><p>Budget Balance: <strong style="color:red">' . number_format($budgetBalance, 2) . '</strong></p>');
    }

    protected function getHeaderActions(): array
    {
        return [
            // The balance is calculated, so deleting needs no budget bookkeeping.
            Actions\DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        // Money available for this expense = current balance + what this
        // expense already uses (it is released and replaced by the new total).
        $available = Budget::balance() + (float) $this->record->total_amount;
        $newTotal = Expense::computeTotal(array_merge($this->record->only(['discount', 'tax']), $this->data));

        if ($newTotal > $available) {
            \Filament\Notifications\Notification::make()
                ->title('Insufficient budget!')
                ->danger()
                ->body('The new total (' . number_format($newTotal, 2) . ') is more than the available budget (' . number_format($available, 2) . ').')
                ->send();

            $this->halt();
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
