<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'business_id',
        'category_id',
        'name',
        'unit',
        'purchase_price',
        'sale_price',
        'stock_quantity',
        'alert_threshold',
        'image_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'stock_quantity' => 'decimal:2',
            'alert_threshold' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
