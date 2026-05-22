<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    protected $fillable = [
        'owner_id',
        'name',
        'phone',
        'address',
        'ifu',
        'slogan',
        'logo_path',
        'primary_color',
        'secondary_color',
        'whatsapp_phone',
        'show_logo_on_documents',
        'show_ifu_on_documents',
        'show_slogan_on_documents',
        'show_address_on_documents',
    ];

    protected function casts(): array
    {
        return [
            'show_logo_on_documents' => 'boolean',
            'show_ifu_on_documents' => 'boolean',
            'show_slogan_on_documents' => 'boolean',
            'show_address_on_documents' => 'boolean',
        ];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'can_edit_prices'])
            ->withTimestamps();
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
