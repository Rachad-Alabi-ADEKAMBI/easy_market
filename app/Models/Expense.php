<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = ['business_id', 'name', 'category', 'type', 'amount', 'spent_on', 'notes'];

    protected function casts(): array
    {
        return ['spent_on' => 'date'];
    }
}
