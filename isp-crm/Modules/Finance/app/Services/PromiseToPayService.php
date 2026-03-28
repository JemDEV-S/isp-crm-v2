<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Carbon\Carbon;
use Modules\Finance\Entities\PromiseToPay;
use Modules\Finance\Enums\PromiseStatus;
use Modules\Finance\Events\PromiseToPayBroken;
use Modules\Finance\Events\PromiseToPayCreated;
use Modules\Finance\Events\PromiseToPayApproved;
use Modules\Finance\Events\PromiseToPayFulfilled;

class PromiseToPayService
{
    public function create(array $data): PromiseToPay
    {
        $maxDays = config('finance.dunning.promise_max_days', 7);
        $maxExtensions = config('finance.dunning.promise_max_extensions', 1);

        $promiseDate = Carbon::parse($data['promise_date']);

        if ($promiseDate->gt(now()->addDays($maxDays))) {
            throw new \DomainException("La fecha de promesa no puede exceder {$maxDays} días desde hoy");
        }

        // Verificar que no haya otra promesa activa
        if ($this->hasActivePromise($data['subscription_id'])) {
            throw new \DomainException('Ya existe una promesa de pago activa para esta suscripción');
        }

        $promise = PromiseToPay::create([
            'subscription_id' => $data['subscription_id'],
            'customer_id' => $data['customer_id'],
            'invoice_id' => $data['invoice_id'] ?? null,
            'promised_amount' => $data['promised_amount'],
            'promise_date' => $promiseDate,
            'status' => PromiseStatus::PENDING,
            'source_channel' => $data['source_channel'],
            'notes' => $data['notes'] ?? null,
            'max_extensions' => $maxExtensions,
            'extensions_used' => 0,
            'created_by' => auth()->id(),
        ]);

        event(new PromiseToPayCreated($promise));

        return $promise;
    }

    public function approve(PromiseToPay $promise, int $approvedBy): PromiseToPay
    {
        $promise->update([
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        event(new PromiseToPayApproved($promise));

        return $promise->fresh();
    }

    public function fulfill(PromiseToPay $promise): PromiseToPay
    {
        $promise->update([
            'status' => PromiseStatus::FULFILLED,
            'fulfilled_at' => now(),
        ]);

        event(new PromiseToPayFulfilled($promise));

        return $promise->fresh();
    }

    public function breakPromise(PromiseToPay $promise): PromiseToPay
    {
        $promise->update([
            'status' => PromiseStatus::BROKEN,
            'broken_at' => now(),
        ]);

        event(new PromiseToPayBroken($promise));

        return $promise->fresh();
    }

    public function cancel(PromiseToPay $promise, string $reason): PromiseToPay
    {
        $promise->update([
            'status' => PromiseStatus::CANCELLED,
            'notes' => $promise->notes ? $promise->notes . "\nCancelada: {$reason}" : "Cancelada: {$reason}",
        ]);

        return $promise->fresh();
    }

    public function extend(PromiseToPay $promise, Carbon $newDate): PromiseToPay
    {
        if (!$promise->canBeExtended()) {
            throw new \DomainException('La promesa ha alcanzado el máximo de extensiones permitidas');
        }

        $maxDays = config('finance.dunning.promise_max_days', 7);

        if ($newDate->gt(now()->addDays($maxDays))) {
            throw new \DomainException("La nueva fecha no puede exceder {$maxDays} días desde hoy");
        }

        $promise->update([
            'promise_date' => $newDate,
            'extensions_used' => $promise->extensions_used + 1,
        ]);

        return $promise->fresh();
    }

    public function processExpired(): int
    {
        $count = 0;

        PromiseToPay::where('status', PromiseStatus::PENDING)
            ->where('promise_date', '<', now()->startOfDay())
            ->cursor()
            ->each(function (PromiseToPay $promise) use (&$count) {
                $this->breakPromise($promise);
                $count++;
            });

        return $count;
    }

    public function hasActivePromise(int $subscriptionId): bool
    {
        return PromiseToPay::where('subscription_id', $subscriptionId)
            ->where('status', PromiseStatus::PENDING)
            ->where('promise_date', '>=', now()->startOfDay())
            ->exists();
    }
}
