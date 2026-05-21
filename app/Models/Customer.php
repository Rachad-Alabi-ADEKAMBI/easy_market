<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['business_id', 'civility', 'name', 'phone', 'email', 'address'];
}
