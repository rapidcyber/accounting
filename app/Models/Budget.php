<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A budget row is money added to the fund (an allocation).
 * The cash on hand is never stored; it is always calculated.
 */
class Budget extends Model
{
    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'datetime',
    ];

    public static function totalAllocated(): float
    {
        return (float) static::query()->sum('amount');
    }

    /**
     * Cash on hand = all money added - all expenses that are not deleted.
     */
    public static function balance(): float
    {
        return round(static::totalAllocated() - Expense::totalSpent(), 2);
    }
}
