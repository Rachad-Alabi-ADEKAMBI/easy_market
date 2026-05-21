<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receivable extends Model
{
    protected $fillable = ['business_id', 'customer_id', 'amount_due', 'amount_paid', 'due_date', 'notes', 'status'];

    protected function casts(): array
    {
        return ['due_date' => 'date'];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
