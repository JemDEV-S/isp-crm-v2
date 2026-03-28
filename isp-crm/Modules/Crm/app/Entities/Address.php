<?php

declare(strict_types=1);

namespace Modules\Crm\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\AccessControl\Entities\Zone;
use Modules\Crm\Enums\AddressType;

class Address extends Model
{
    protected $fillable = [
        'uuid',
        'customer_id',
        'type',
        'label',
        'street',
        'number',
        'floor',
        'apartment',
        'reference',
        'address_reference',
        'photo_url',
        'district',
        'city',
        'province',
        'postal_code',
        'latitude',
        'longitude',
        'georeference_quality',
        'zone_id',
        'is_default',
    ];

    protected $casts = [
        'type' => AddressType::class,
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_default' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Address $address) {
            if (empty($address->uuid)) {
                $address->uuid = (string) Str::uuid();
            }
        });

        static::saved(function (Address $address) {
            if ($address->is_default) {
                Address::where('customer_id', $address->customer_id)
                    ->where('type', $address->type)
                    ->where('id', '!=', $address->id)
                    ->update(['is_default' => false]);
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->street,
            $this->number ? "#{$this->number}" : null,
            $this->floor ? "Piso {$this->floor}" : null,
            $this->apartment ? "Dpto. {$this->apartment}" : null,
        ]);

        $address = implode(' ', $parts);
        $location = implode(', ', array_filter([$this->district, $this->city, $this->province]));

        return "{$address}, {$location}";
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function getCoordinates(): ?array
    {
        if (!$this->hasCoordinates()) {
            return null;
        }

        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
        ];
    }

    public function isServiceAddress(): bool
    {
        return $this->type === AddressType::SERVICE;
    }

    public function isBillingAddress(): bool
    {
        return $this->type === AddressType::BILLING;
    }

    public function scopeService($query)
    {
        return $query->where('type', AddressType::SERVICE);
    }

    public function scopeBilling($query)
    {
        return $query->where('type', AddressType::BILLING);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeInZone($query, int $zoneId)
    {
        return $query->where('zone_id', $zoneId);
    }

    public function scopeNearby($query, float $lat, float $lng, int $radiusMeters = 500)
    {
        $latDistance = $radiusMeters / 111000;
        $lngDistance = $radiusMeters / (111000 * cos(deg2rad($lat)));

        return $query->whereBetween('latitude', [$lat - $latDistance, $lat + $latDistance])
                     ->whereBetween('longitude', [$lng - $lngDistance, $lng + $lngDistance]);
    }
}
