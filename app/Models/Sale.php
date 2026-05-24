<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'business_id',
        'customer_id',
        'seller_id',
        'canceled_by',
        'number',
        'type',
        'payment_method',
        'status',
        'subtotal',
        'discount',
        'total',
        'credit_due_date',
        'sold_at',
        'canceled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'credit_due_date' => 'date',
            'sold_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function canceledBy()
    {
        return $this->belongsTo(User::class, 'canceled_by');
    }
}
