<?php

declare(strict_types=1);

namespace Modules\Finance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Crm\Entities\Customer;
use Modules\Finance\Enums\WalletConcept;
use Modules\Finance\Enums\WalletTransactionType;

class Wallet extends Model
{
    protected $fillable = [
        'customer_id',
        'balance',
        'credit_limit',
        'status',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function credit(
        float $amount,
        string $concept,
        ?string $description = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): WalletTransaction {
        $this->increment('balance', $amount);
        $this->refresh();

        return $this->transactions()->create([
            'type' => WalletTransactionType::CREDIT,
            'amount' => $amount,
            'balance_after' => $this->balance,
            'concept' => $concept,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'created_by' => auth()->id(),
        ]);
    }

    public function debit(
        float $amount,
        string $concept,
        ?string $description = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): WalletTransaction {
        $available = (float) $this->balance + (float) $this->credit_limit;

        if ($amount > $available) {
            throw new \RuntimeException("Saldo insuficiente. Disponible: {$available}, Requerido: {$amount}");
        }

        $this->decrement('balance', $amount);
        $this->refresh();

        return $this->transactions()->create([
            'type' => WalletTransactionType::DEBIT,
            'amount' => $amount,
            'balance_after' => $this->balance,
            'concept' => $concept,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'created_by' => auth()->id(),
        ]);
    }
}
