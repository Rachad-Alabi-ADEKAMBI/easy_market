<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryAdvance extends Model
{
    protected $fillable = ['business_id', 'employee_id', 'amount', 'advanced_on', 'notes'];

    protected function casts(): array
    {
        return ['advanced_on' => 'date'];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
