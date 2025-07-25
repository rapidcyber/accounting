<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    public function expenses()
    {
        return $this->belongsToMany(Expense::class);
    }
}
