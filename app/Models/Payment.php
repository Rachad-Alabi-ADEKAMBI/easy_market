<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['business_id', 'sale_id', 'receivable_id', 'type', 'method', 'amount', 'reference', 'paid_at'];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime'];
    }
}
