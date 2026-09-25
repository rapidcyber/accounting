<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Budgets used to be a chain of running-balance snapshots: every expense
 * created a new budget row holding "previous balance - expense", and the
 * balance shown in the app was simply the latest row. Editing or deleting an
 * older expense patched an old snapshot and never reached the current
 * balance, so the numbers drifted.
 *
 * From now on the budgets table only records money that was actually added
 * (allocations), and the cash on hand is always calculated:
 *
 *     cash on hand = SUM(budgets.amount) - SUM(active expenses.total_amount)
 *
 * This migration:
 *  1. Renames the old tables to legacy_budget_snapshots / legacy_budget_expense
 *     (kept untouched for audit).
 *  2. Creates a new budgets table and fills it with the amount added by each
 *     old "top-up" row (its snapshot minus the snapshot before it).
 *  3. Adds labelled adjustment entries, dated where they happened, for every
 *     old balance change an expense does not explain (balances edited by hand,
 *     expenses edited or never deducted), so the running balance in Budget
 *     History matches what the app showed at the time and the calculated cash
 *     on hand equals the balance shown right before the migration.
 *     Nothing shown to users changes on deploy; only future edits become correct.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop the pivot's foreign keys first: MySQL keeps constraint names on
        // rename, and the old names would block ever creating a table called
        // budget_expense again (e.g. when re-importing an old database dump).
        Schema::table('budget_expense', function (Blueprint $table) {
            $table->dropForeign(['budget_id']);
            $table->dropForeign(['expense_id']);
        });

        Schema::rename('budget_expense', 'legacy_budget_expense');
        Schema::rename('budgets', 'legacy_budget_snapshots');

        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 15, 2);
            $table->dateTime('date');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $snapshots = DB::table('legacy_budget_snapshots')
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        if ($snapshots->isEmpty()) {
            return;
        }

        $expenseByBudget = DB::table('legacy_budget_expense')
            ->join('expenses', 'expenses.id', '=', 'legacy_budget_expense.expense_id')
            ->get(['legacy_budget_expense.budget_id', 'expenses.id', 'expenses.total_amount', 'expenses.deleted_at'])
            ->keyBy('budget_id');

        $previous = 0.0;
        $rows = [];

        foreach ($snapshots as $snapshot) {
            $amount = (float) $snapshot->amount;
            $change = round($amount - $previous, 2);
            $previous = $amount;
            $expense = $expenseByBudget[$snapshot->id] ?? null;

            if (! $expense) {
                // A top-up: the money that was added.
                if ($change != 0.0) {
                    $rows[] = $this->entry($change, $snapshot->date, $snapshot->description, $snapshot->created_at);
                }

                continue;
            }

            // An expense snapshot. The expense itself is now deducted
            // automatically (if it is not deleted), so keep only the part of
            // the old change that the expense does not explain, e.g. a balance
            // edited by hand or an expense edited without fixing the balance.
            $explained = $expense->deleted_at === null ? -(float) $expense->total_amount : 0.0;
            $unexplained = round($change - $explained, 2);

            if ($unexplained != 0.0) {
                $rows[] = $this->entry(
                    $unexplained,
                    $snapshot->date,
                    'Adjustment from old balance history (expense #' . $expense->id . ')',
                    $snapshot->created_at
                );
            }
        }

        // Expenses that the old balance never deducted (their snapshot row was
        // deleted). Offset them where they happened so the history keeps the
        // balance people saw.
        $neverDeducted = DB::table('expenses')
            ->whereNull('deleted_at')
            ->whereNotIn('id', DB::table('legacy_budget_expense')->select('expense_id'))
            ->get(['id', 'date', 'total_amount', 'created_at']);

        foreach ($neverDeducted as $expense) {
            $rows[] = $this->entry(
                round((float) $expense->total_amount, 2),
                $expense->date . ' 00:00:00',
                'Adjustment: expense #' . $expense->id . ' was never deducted from the old balance',
                $expense->created_at
            );
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('budgets')->insert($chunk);
        }

        // Anything still unexplained, so the balance stays exactly what users
        // saw right before this migration.
        $shownBalance = (float) $snapshots->last()->amount;
        $allocated = (float) DB::table('budgets')->sum('amount');
        $spent = (float) DB::table('expenses')->whereNull('deleted_at')->sum('total_amount');
        $remaining = round($shownBalance - ($allocated - $spent), 2);

        if ($remaining != 0.0) {
            DB::table('budgets')->insert($this->entry(
                $remaining,
                now(),
                'Reconciliation: difference from the old running balance (see legacy_budget_snapshots)',
                now()
            ));
        }
    }

    private function entry(float $amount, $date, ?string $description, $createdAt): array
    {
        return [
            'amount' => $amount,
            'date' => $date,
            'description' => $description,
            'created_at' => $createdAt ?? now(),
            'updated_at' => $createdAt ?? now(),
        ];
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
        Schema::rename('legacy_budget_snapshots', 'budgets');
        Schema::rename('legacy_budget_expense', 'budget_expense');

        Schema::table('budget_expense', function (Blueprint $table) {
            $table->foreign('budget_id')->references('id')->on('budgets')->cascadeOnDelete();
            $table->foreign('expense_id')->references('id')->on('expenses')->cascadeOnDelete();
        });
    }
};
