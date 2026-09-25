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
 *  3. Adds one clearly labelled reconciliation entry so the calculated cash on
 *     hand equals the balance the app showed right before the migration.
 *     Nothing shown to users changes on deploy; only future edits become correct.
 */
return new class extends Migration
{
    public function up(): void
    {
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

        $linkedToExpense = DB::table('legacy_budget_expense')
            ->pluck('budget_id')
            ->flip();

        $previous = 0.0;
        $rows = [];

        foreach ($snapshots as $snapshot) {
            $amount = (float) $snapshot->amount;

            if (! isset($linkedToExpense[$snapshot->id])) {
                $added = round($amount - $previous, 2);

                if ($added != 0.0) {
                    $rows[] = [
                        'amount' => $added,
                        'date' => $snapshot->date,
                        'description' => $snapshot->description,
                        'created_at' => $snapshot->created_at,
                        'updated_at' => $snapshot->updated_at,
                    ];
                }
            }

            $previous = $amount;
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('budgets')->insert($chunk);
        }

        // The balance users saw right before this migration.
        $shownBalance = (float) $snapshots->last()->amount;

        $allocated = (float) DB::table('budgets')->sum('amount');
        $spent = (float) DB::table('expenses')->whereNull('deleted_at')->sum('total_amount');
        $adjustment = round($shownBalance - ($allocated - $spent), 2);

        if ($adjustment != 0.0) {
            DB::table('budgets')->insert([
                'amount' => $adjustment,
                'date' => now(),
                'description' => 'Reconciliation: difference from the old running balance (see legacy_budget_snapshots)',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
        Schema::rename('legacy_budget_snapshots', 'budgets');
        Schema::rename('legacy_budget_expense', 'budget_expense');
    }
};
