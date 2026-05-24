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
        'description',
        'logo_path',
        'primary_color',
        'secondary_color',
        'whatsapp_phone',
        'whatsapp_reports_enabled',
        'whatsapp_report_time',
        'whatsapp_report_type',
        'whatsapp_report_phone',
        'show_logo_on_documents',
        'show_ifu_on_documents',
        'show_slogan_on_documents',
        'show_description_on_documents',
        'show_phone_on_documents',
        'show_whatsapp_on_documents',
        'show_address_on_documents',
    ];

    protected function casts(): array
    {
        return [
            'show_logo_on_documents' => 'boolean',
            'show_ifu_on_documents' => 'boolean',
            'show_slogan_on_documents' => 'boolean',
            'show_description_on_documents' => 'boolean',
            'show_phone_on_documents' => 'boolean',
            'show_whatsapp_on_documents' => 'boolean',
            'show_address_on_documents' => 'boolean',
            'whatsapp_reports_enabled' => 'boolean',
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

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
