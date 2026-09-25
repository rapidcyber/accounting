<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Read-only budget history: every budget added (money in) and every expense
 * that is not deleted (money out), in the order they were recorded, with a
 * running balance.
 *
 * It is built live from the budgets and expenses tables on every request, so it
 * can never fall out of sync with them. The last running balance always equals
 * Budget::balance().
 */
class LedgerEntry extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'entry_date' => 'date',
        'money_in' => 'decimal:2',
        'money_out' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function newQuery(): Builder
    {
        return parent::newQuery()->fromSub(static::ledgerSql(), 'ledger');
    }

    protected static function ledgerSql(): \Illuminate\Database\Query\Builder
    {
        $budgets = DB::table('budgets')->selectRaw("
            'budget' as entry_type,
            id as source_id,
            date(date) as entry_date,
            coalesce(created_at, date) as recorded_at,
            0 as type_order,
            coalesce(description, 'NEW BUDGET') as description,
            amount as money_in,
            0 as money_out
        ");

        $expenses = DB::table('expenses')->whereNull('deleted_at')->selectRaw("
            'expense' as entry_type,
            id as source_id,
            date as entry_date,
            created_at as recorded_at,
            1 as type_order,
            description,
            0 as money_in,
            total_amount as money_out
        ");

        $all = $budgets->unionAll($expenses);

        // Entries run in the order they were recorded (as the old history did),
        // not by the date typed on them: expenses are often entered days
        // later, together with the money that paid for them.
        $order = 'recorded_at, type_order, source_id';

        $key = DB::connection()->getDriverName() === 'sqlite'
            ? "entry_type || '-' || source_id"
            : "concat(entry_type, '-', source_id)";

        return DB::query()->fromSub($all, 'entries')->selectRaw("
            entries.*,
            {$key} as id,
            sum(money_in - money_out) over (order by {$order} rows between unbounded preceding and current row) as balance,
            row_number() over (order by {$order}) as position
        ");
    }
}
