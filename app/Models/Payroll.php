<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $fillable = ['business_id', 'employee_id', 'gross_salary', 'salary_advance', 'net_salary', 'period', 'status', 'paid_at'];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime'];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
