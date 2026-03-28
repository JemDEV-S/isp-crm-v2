<?php

declare(strict_types=1);

namespace Modules\Crm\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\AccessControl\Entities\User;
use Modules\AccessControl\Entities\Zone;
use Modules\Crm\Enums\DocumentType;
use Modules\Crm\Enums\LeadSource;
use Modules\Crm\Enums\LeadStatus;

class Lead extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'document_type',
        'document_number',
        'phone',
        'email',
        'source',
        'status',
        'is_duplicate',
        'duplicate_of_id',
        'duplicate_resolution',
        'notes',
        'zone_id',
        'assigned_to',
        'converted_at',
        'created_by',
    ];

    protected $casts = [
        'source' => LeadSource::class,
        'status' => LeadStatus::class,
        'document_type' => DocumentType::class,
        'is_duplicate' => 'boolean',
        'converted_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Lead $lead) {
            if (empty($lead->uuid)) {
                $lead->uuid = (string) Str::uuid();
            }
            if (empty($lead->created_by) && auth()->check()) {
                $lead->created_by = auth()->id();
            }
        });
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'duplicate_of_id');
    }

    public function duplicates(): HasMany
    {
        return $this->hasMany(self::class, 'duplicate_of_id');
    }

    public function feasibilityRequests(): HasMany
    {
        return $this->hasMany(FeasibilityRequest::class);
    }

    public function capacityReservations(): HasMany
    {
        return $this->hasMany(CapacityReservation::class);
    }

    public function isConverted(): bool
    {
        return $this->converted_at !== null;
    }

    public function isWon(): bool
    {
        return $this->status === LeadStatus::WON;
    }

    public function isLost(): bool
    {
        return $this->status === LeadStatus::LOST;
    }

    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }

    public function isDuplicate(): bool
    {
        return $this->is_duplicate;
    }

    public function scopeStatus($query, LeadStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSource($query, LeadSource $source)
    {
        return $query->where('source', $source);
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeInZone($query, int $zoneId)
    {
        return $query->where('zone_id', $zoneId);
    }

    public function scopeNotConverted($query)
    {
        return $query->whereNull('converted_at');
    }

    public function scopeConverted($query)
    {
        return $query->whereNotNull('converted_at');
    }
}
