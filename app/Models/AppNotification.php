<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $table = 'notifications';

    protected $fillable = ['business_id', 'user_id', 'type', 'title', 'message', 'channel', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }
}
