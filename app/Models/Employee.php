<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = ['business_id', 'user_id', 'name', 'position', 'type', 'salary', 'hired_at', 'is_active'];

    protected function casts(): array
    {
        return [
            'hired_at' => 'date',
            'is_active' => 'boolean',
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
