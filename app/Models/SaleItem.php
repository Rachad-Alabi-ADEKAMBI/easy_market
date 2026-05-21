<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = ['sale_id', 'product_id', 'product_name', 'quantity', 'unit_price', 'discount', 'total'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2'];
    }
}
