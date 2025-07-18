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

    public function budgets()
    {
        return $this->belongsToMany(Budget::class);
    }
}
