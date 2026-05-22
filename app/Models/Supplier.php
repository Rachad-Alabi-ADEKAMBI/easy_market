<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = ['business_id', 'name', 'phone', 'payment_terms', 'notes'];
}
