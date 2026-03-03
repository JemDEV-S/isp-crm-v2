<?php

declare(strict_types=1);

namespace Modules\Crm\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Crm\Enums\ContactType;

class Contact extends Model
{
    protected $fillable = [
        'customer_id',
        'name',
        'relationship',
        'type',
        'value',
        'is_primary',
        'receives_notifications',
    ];

    protected $casts = [
        'type' => ContactType::class,
        'is_primary' => 'boolean',
        'receives_notifications' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saved(function (Contact $contact) {
            if ($contact->is_primary) {
                Contact::where('customer_id', $contact->customer_id)
                    ->where('id', '!=', $contact->id)
                    ->update(['is_primary' => false]);
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isPhone(): bool
    {
        return $this->type === ContactType::PHONE;
    }

    public function isEmail(): bool
    {
        return $this->type === ContactType::EMAIL;
    }

    public function isWhatsApp(): bool
    {
        return $this->type === ContactType::WHATSAPP;
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeForNotifications($query)
    {
        return $query->where('receives_notifications', true);
    }

    public function scopeOfType($query, ContactType $type)
    {
        return $query->where('type', $type);
    }
}
