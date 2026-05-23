<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = ['business_id', 'user_id', 'name', 'position', 'type', 'salary', 'salary_payment_date', 'hired_at', 'is_active', 'banned_at', 'ban_reason'];

    protected function casts(): array
    {
        return [
            'salary_payment_date' => 'date',
            'hired_at' => 'date',
            'is_active' => 'boolean',
            'banned_at' => 'datetime',
        ];
    }

    public function advances()
    {
        return $this->hasMany(SalaryAdvance::class);
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
