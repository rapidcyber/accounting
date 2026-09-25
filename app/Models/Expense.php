<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    public function vouchers()
    {
        return $this->belongsToMany(Voucher::class);
    }

    /**
     * Sum of all expenses that are not deleted.
     */
    public static function totalSpent(): float
    {
        return (float) static::query()->sum('total_amount');
    }

    /**
     * Total of an expense from raw form values, using the same formula as the
     * total_amount database column (amount * quantity - discount + tax).
     */
    public static function computeTotal(array $data): float
    {
        return round(
            (float) ($data['amount'] ?? 0) * (float) ($data['quantity'] ?? 0)
            - (float) ($data['discount'] ?? 0)
            + (float) ($data['tax'] ?? 0),
            2
        );
    }
}
