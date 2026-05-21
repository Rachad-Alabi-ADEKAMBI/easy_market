<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierDebt extends Model
{
    protected $fillable = ['business_id', 'supplier_id', 'amount_due', 'amount_paid', 'due_date', 'status'];

    protected function casts(): array
    {
        return ['due_date' => 'date'];
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
