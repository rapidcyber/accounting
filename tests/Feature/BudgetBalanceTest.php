<?php

use App\Filament\Resources\ExpenseResource\Pages\CreateExpense;
use App\Filament\Resources\ExpenseResource\Pages\EditExpense;
use App\Models\Budget;
use App\Models\Expense;
use App\Models\User;
use App\Support\ExpensePeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function addBudget(float $amount, string $date = '2026-09-01 08:00:00'): Budget
{
    return Budget::create(['amount' => $amount, 'date' => $date, 'description' => 'NEW BUDGET']);
}

function addExpense(User $user, float $amount, int $quantity = 1, string $date = '2026-09-10'): Expense
{
    return Expense::create([
        'description' => 'Test expense',
        'amount' => $amount,
        'quantity' => $quantity,
        'date' => $date,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ])->refresh();
}

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('cash on hand is money added minus active expenses', function () {
    addBudget(100000);
    addBudget(50000);
    addExpense($this->user, 1000, 3);
    addExpense($this->user, 2500);

    expect(Budget::balance())->toBe(144500.0);
});

test('editing an older expense changes the current balance', function () {
    addBudget(100000);
    $old = addExpense($this->user, 10000);
    addExpense($this->user, 500);
    addExpense($this->user, 700);

    $old->update(['amount' => 1000]);

    expect(Budget::balance())->toBe(97800.0);
});

test('deleting an expense returns it to the balance and restoring deducts it again', function () {
    addBudget(10000);
    $expense = addExpense($this->user, 3000);
    addExpense($this->user, 1000);

    $expense->delete();
    expect(Budget::balance())->toBe(9000.0);

    $expense->restore();
    expect(Budget::balance())->toBe(6000.0);
});

test('deleting a budget entry removes its amount from the balance', function () {
    addBudget(10000);
    $second = addBudget(5000);
    addExpense($this->user, 2000);

    $second->delete();

    expect(Budget::balance())->toBe(8000.0);
});

test('creating an expense checks the full total, not the unit amount', function () {
    addBudget(1000);
    $this->actingAs($this->user);

    Livewire::test(CreateExpense::class)
        ->fillForm([
            'date' => now()->toDateString(),
            'quantity' => 3,
            'amount' => 500, // total 1,500 > balance 1,000
            'description' => 'Too expensive',
            'payment_method' => 'cash',
        ])
        ->call('create');

    expect(Expense::count())->toBe(0)
        ->and(Budget::balance())->toBe(1000.0);
});

test('creating an expense deducts its full total from the balance', function () {
    addBudget(1000);
    $this->actingAs($this->user);

    Livewire::test(CreateExpense::class)
        ->fillForm([
            'date' => now()->toDateString(),
            'quantity' => 2,
            'amount' => 300,
            'description' => 'Two items',
            'payment_method' => 'cash',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Expense::count())->toBe(1)
        ->and(Budget::balance())->toBe(400.0)
        ->and(Budget::count())->toBe(1); // no snapshot rows are created any more
});

test('an expense date with a wrong year is rejected', function () {
    addBudget(1000);
    $this->actingAs($this->user);

    Livewire::test(CreateExpense::class)
        ->fillForm([
            'date' => '0002-04-15',
            'quantity' => 1,
            'amount' => 100,
            'description' => 'Typo date',
            'payment_method' => 'cash',
        ])
        ->call('create')
        ->assertHasFormErrors(['date']);

    expect(Expense::count())->toBe(0);
});

test('editing an expense can use its own amount plus the remaining balance', function () {
    addBudget(1000);
    $expense = addExpense($this->user, 800);
    $this->actingAs($this->user);

    // Balance is 200, so 950 is allowed (800 released + 200 remaining = 1,000).
    Livewire::test(EditExpense::class, ['record' => $expense->getRouteKey()])
        ->fillForm(['amount' => 950])
        ->call('save')
        ->assertHasNoFormErrors();

    expect((float) $expense->refresh()->total_amount)->toBe(950.0)
        ->and(Budget::balance())->toBe(50.0);

    // 1,100 is more than the 1,000 available: blocked, nothing changes.
    Livewire::test(EditExpense::class, ['record' => $expense->getRouteKey()])
        ->fillForm(['amount' => 1100])
        ->call('save');

    expect((float) $expense->refresh()->total_amount)->toBe(950.0);
});

test('period ranges cover the right dates', function () {
    $this->travelTo('2026-09-25 10:00:00');

    expect(ExpensePeriod::range('annually'))->toBe(['date_from' => '2026-01-01', 'date_to' => '2026-12-31'])
        ->and(ExpensePeriod::range('monthly'))->toBe(['date_from' => '2026-09-01', 'date_to' => '2026-09-30'])
        ->and(ExpensePeriod::range(null, '2026-09-10', null))->toBe(['date_from' => '2026-09-10', 'date_to' => null])
        ->and(ExpensePeriod::range('monthly', '2026-09-10', '2026-10-15'))->toBe(['date_from' => '2026-09-10', 'date_to' => '2026-09-30']);
});

test('this month does not include the same month of another year', function () {
    $this->travelTo('2026-09-25 10:00:00');
    addExpense($this->user, 100, 1, '2026-09-05');
    addExpense($this->user, 999, 1, '2025-09-05');

    $total = ExpensePeriod::apply(Expense::query(), ExpensePeriod::range('monthly'))->sum('total_amount');

    expect((float) $total)->toBe(100.0);
});

test('migration keeps the balance users saw and converts top-ups to amounts added', function () {
    $migration = require database_path('migrations/2026_09_25_000000_convert_budgets_to_allocations.php');
    $migration->down();

    $u = $this->user->id;
    $e1 = DB::table('expenses')->insertGetId(['description' => 'a', 'amount' => 300, 'date' => '2026-09-02', 'created_by' => $u, 'updated_by' => $u]);
    $e2 = DB::table('expenses')->insertGetId(['description' => 'b', 'amount' => 1000, 'date' => '2026-09-03', 'created_by' => $u, 'updated_by' => $u]);
    // Its snapshot row was deleted, so the old balance never deducted it.
    $e3 = DB::table('expenses')->insertGetId(['description' => 'c', 'amount' => 200, 'date' => '2026-09-03', 'created_by' => $u, 'updated_by' => $u]);

    // Old snapshot chain: +10,000, -300, +5,000, -10,000 (expense later edited to 1,000 without fixing the balance)
    DB::table('budgets')->insert([
        ['id' => 1, 'amount' => 10000, 'date' => '2026-09-01 08:00:00', 'description' => 'NEW BUDGET'],
        ['id' => 2, 'amount' => 9700, 'date' => '2026-09-02 08:00:00', 'description' => 'after expense a'],
        ['id' => 3, 'amount' => 14700, 'date' => '2026-09-02 09:00:00', 'description' => 'NEW BUDGET'],
        ['id' => 4, 'amount' => 4700, 'date' => '2026-09-03 08:00:00', 'description' => 'after expense b'],
    ]);
    DB::table('budget_expense')->insert([
        ['budget_id' => 2, 'expense_id' => $e1],
        ['budget_id' => 4, 'expense_id' => $e2],
    ]);

    $migration->up();

    expect(Schema::hasTable('legacy_budget_snapshots'))->toBeTrue()
        ->and(Budget::where('description', 'NEW BUDGET')->pluck('amount')->map(fn ($a) => (float) $a)->all())->toBe([10000.0, 5000.0])
        // Shown before migration: 4,700. Expense b now counts as 1,000 but the old
        // balance dropped by 10,000, so a -9,000 adjustment is dated where it happened.
        ->and(Budget::balance())->toBe(4700.0)
        ->and((float) Budget::where('description', 'like', 'Adjustment from old balance history (expense #' . $e2 . ')')->value('amount'))->toBe(-9000.0)
        ->and((float) Budget::where('description', 'like', 'Adjustment: expense #' . $e3 . ' %')->value('amount'))->toBe(200.0)
        ->and(Budget::where('description', 'like', 'Reconciliation%')->exists())->toBeFalse()
        ->and((float) \App\Models\LedgerEntry::query()->orderByDesc('position')->value('balance'))->toBe(4700.0);
});

test('expense and budget list pages load and show the calculated balance', function () {
    addBudget(5000);
    addExpense($this->user, 1200);
    $this->actingAs($this->user);

    Livewire::test(\App\Filament\Resources\ExpenseResource\Pages\ListExpenses::class)
        ->assertSuccessful()
        ->assertSee('Budget Balance: 3,800.00');

    Livewire::test(\App\Filament\Resources\BudgetResource\Pages\ListBudgets::class)
        ->assertSuccessful()
        ->assertSee('Cash on Hand: 3,800.00');
});

test('adding a budget stores only the amount added', function () {
    addBudget(5000);
    $this->actingAs($this->user);

    Livewire::test(\App\Filament\Resources\BudgetResource\Pages\CreateBudget::class)
        ->fillForm(['amount' => 100000, 'date' => now(), 'description' => 'NEW BUDGET'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect((float) Budget::latest('id')->value('amount'))->toBe(100000.0)
        ->and(Budget::balance())->toBe(105000.0);
});

test('budget history lists money added and spent with a running balance that matches cash on hand', function () {
    // Recorded out of order on purpose: the history follows each entry's date.
    $this->travelTo('2026-09-05 08:00:00');
    addExpense($this->user, 500, 2, '2026-09-03');   // entered late
    addBudget(10000, '2026-09-01 08:00:00');
    $old = addExpense($this->user, 3000, 1, '2026-09-02');
    addBudget(5000, '2026-09-03 09:00:00');           // same day as an expense: listed first
    $deleted = addExpense($this->user, 700, 1, '2026-09-04');
    $deleted->delete();

    $old->update(['amount' => 1000]); // edit an older expense

    $rows = \App\Models\LedgerEntry::query()->orderBy('position')->get();

    expect($rows->map(fn ($r) => $r->entry_date->toDateString())->all())->toBe(['2026-09-01', '2026-09-02', '2026-09-03', '2026-09-03'])
        ->and($rows->pluck('entry_type')->all())->toBe(['budget', 'expense', 'budget', 'expense'])
        ->and($rows->pluck('balance')->map(fn ($b) => (float) $b)->all())->toBe([10000.0, 9000.0, 14000.0, 13000.0])
        ->and((float) $rows->last()->balance)->toBe(Budget::balance());
});

test('budget history page and print load', function () {
    addBudget(5000);
    addExpense($this->user, 1200);
    $this->actingAs($this->user);

    Livewire::test(\App\Filament\Pages\BudgetHistory::class)
        ->assertSuccessful()
        ->assertSee('Cash on Hand: 3,800.00')
        ->assertSee('Test expense');

    $this->get(route('budgets.print'))
        ->assertOk()
        ->assertSee('Test expense')
        ->assertSee('3,800.00');
});

test('budget history print heading shows the selected dates', function () {
    addBudget(5000, '2026-09-01 08:00:00');
    addExpense($this->user, 1200, 1, '2026-09-03');
    $this->actingAs($this->user);

    $this->get(route('budgets.print', ['date_from' => '2026-09-01', 'date_to' => '2026-09-30']))
        ->assertOk()
        ->assertSee('BUDGET HISTORY FROM 09/01/2026 TO 09/30/2026');
});

test('budget history print shows brought forward, totals and ending balance for the dates', function () {
    addBudget(10000, '2026-08-20 08:00:00');
    addExpense($this->user, 2000, 1, '2026-08-25');
    addBudget(5000, '2026-09-01 08:00:00');
    addExpense($this->user, 1500, 1, '2026-09-10');
    addExpense($this->user, 300, 1, '2026-10-02'); // after the period
    $this->actingAs($this->user);

    $this->get(route('budgets.print', ['date_from' => '2026-09-01', 'date_to' => '2026-09-30']))
        ->assertOk()
        ->assertSeeInOrder(['BALANCE BROUGHT FORWARD', '8,000.00', '5,000.00', '1,500.00', 'TOTAL:', '5,000.00', '1,500.00', 'ENDING BALANCE AS OF 09/30/2026:', '11,500.00'])
        ->assertDontSee('Balance</th>', false);
});
