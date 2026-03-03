<?php

declare(strict_types=1);

namespace Modules\Crm\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\AccessControl\Entities\User;
use Modules\Crm\Enums\CustomerType;
use Modules\Crm\Enums\DocumentType;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'code',
        'lead_id',
        'customer_type',
        'document_type',
        'document_number',
        'name',
        'trade_name',
        'phone',
        'email',
        'billing_email',
        'is_active',
        'credit_limit',
        'tax_exempt',
        'created_by',
    ];

    protected $casts = [
        'customer_type' => CustomerType::class,
        'document_type' => DocumentType::class,
        'is_active' => 'boolean',
        'credit_limit' => 'decimal:2',
        'tax_exempt' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Customer $customer) {
            if (empty($customer->uuid)) {
                $customer->uuid = (string) Str::uuid();
            }
            if (empty($customer->code)) {
                $customer->code = self::generateCode();
            }
            if (empty($customer->created_by) && auth()->check()) {
                $customer->created_by = auth()->id();
            }
        });
    }

    public static function generateCode(): string
    {
        $lastCustomer = self::withTrashed()->orderBy('id', 'desc')->first();
        $nextNumber = $lastCustomer ? $lastCustomer->id + 1 : 1;
        return 'CLI-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(CustomerNote::class)->orderBy('is_pinned', 'desc')->orderBy('created_at', 'desc');
    }

    public function getDefaultServiceAddress(): ?Address
    {
        return $this->addresses()
            ->where('type', 'service')
            ->where('is_default', true)
            ->first() ?? $this->addresses()->where('type', 'service')->first();
    }

    public function getDefaultBillingAddress(): ?Address
    {
        return $this->addresses()
            ->where('type', 'billing')
            ->where('is_default', true)
            ->first() ?? $this->addresses()->where('type', 'billing')->first();
    }

    public function getPrimaryContact(): ?Contact
    {
        return $this->contacts()->where('is_primary', true)->first();
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isBusiness(): bool
    {
        return $this->customer_type === CustomerType::BUSINESS;
    }

    public function isPersonal(): bool
    {
        return $this->customer_type === CustomerType::PERSONAL;
    }

    public function getDisplayName(): string
    {
        if ($this->trade_name) {
            return $this->trade_name;
        }
        return $this->name;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopePersonal($query)
    {
        return $query->where('customer_type', CustomerType::PERSONAL);
    }

    public function scopeBusiness($query)
    {
        return $query->where('customer_type', CustomerType::BUSINESS);
    }

    public function scopeByDocument($query, string $documentType, string $documentNumber)
    {
        return $query->where('document_type', $documentType)
                     ->where('document_number', $documentNumber);
    }
}
