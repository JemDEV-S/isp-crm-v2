<?php

declare(strict_types=1);

namespace Modules\Crm\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Exceptions\BusinessLogicException;
use Modules\Crm\Entities\Address;
use Modules\Crm\Entities\CapacityReservation;
use Modules\Crm\Entities\FeasibilityRequest;
use Modules\Crm\Entities\Lead;
use Modules\Crm\Events\CapacityReserved;
use Modules\Crm\Events\FeasibilityConfirmed;
use Modules\Crm\Events\FeasibilityRejected;
use Modules\Network\Entities\NapPort;
use Modules\Network\Enums\NapPortStatus;
use Modules\Network\Services\NetworkProvisioningService;

class FeasibilityService
{
    public function __construct(
        protected NetworkProvisioningService $networkProvisioningService,
    ) {}

    public function check(int $leadId, array $addressData): FeasibilityRequest
    {
        $lead = Lead::findOrFail($leadId);
        [$latitude, $longitude, $addressId, $radiusMeters] = $this->resolveCoordinates($addressData);

        $request = FeasibilityRequest::create([
            'lead_id' => $lead->id,
            'address_id' => $addressId,
            'status' => 'pending',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'radius_meters' => $radiusMeters,
            'requested_at' => now(),
        ]);

        $result = $this->networkProvisioningService->checkFeasibility($latitude, $longitude, $radiusMeters);

        $request->update([
            'status' => $result->isFeasible ? 'confirmed' : 'rejected',
            'result_data' => $result->toArray(),
            'resolved_at' => now(),
        ]);

        $freshRequest = $request->fresh();

        if ($result->isFeasible) {
            event(new FeasibilityConfirmed($freshRequest));
        } else {
            event(new FeasibilityRejected($freshRequest));
        }

        return $freshRequest;
    }

    public function reserveCapacity(
        int $napPortId,
        int $leadId,
        int $hours = 24,
        ?int $feasibilityRequestId = null,
    ): CapacityReservation {
        return DB::transaction(function () use ($napPortId, $leadId, $hours, $feasibilityRequestId) {
            $lead = Lead::findOrFail($leadId);
            $napPort = NapPort::query()->lockForUpdate()->with('napBox')->findOrFail($napPortId);

            if ($napPort->status !== NapPortStatus::FREE) {
                throw new BusinessLogicException('El puerto NAP no está libre para reserva temporal');
            }

            $activeReservationExists = CapacityReservation::query()
                ->where('reservable_type', NapPort::class)
                ->where('reservable_id', $napPortId)
                ->where('status', 'active')
                ->whereNull('released_at')
                ->where('expires_at', '>', now())
                ->exists();

            if ($activeReservationExists) {
                throw new BusinessLogicException('El puerto NAP ya tiene una reserva activa');
            }

            $napPort->update([
                'status' => NapPortStatus::RESERVED,
                'notes' => trim(($napPort->notes ? $napPort->notes . PHP_EOL : '') . "Reservado temporalmente para lead {$lead->id}"),
            ]);

            $reservation = CapacityReservation::create([
                'reservable_type' => NapPort::class,
                'reservable_id' => $napPort->id,
                'lead_id' => $lead->id,
                'feasibility_request_id' => $feasibilityRequestId,
                'status' => 'active',
                'metadata' => [
                    'nap_box_id' => $napPort->nap_box_id,
                    'nap_box_code' => $napPort->napBox?->code,
                    'port_number' => $napPort->port_number,
                ],
                'expires_at' => now()->addHours($hours),
            ]);

            event(new CapacityReserved($reservation->fresh()));

            return $reservation->fresh();
        });
    }

    public function releaseReservation(int $reservationId): void
    {
        DB::transaction(function () use ($reservationId) {
            $reservation = CapacityReservation::query()->lockForUpdate()->findOrFail($reservationId);

            if ($reservation->released_at !== null || $reservation->status === 'released') {
                return;
            }

            $reservation->update([
                'status' => 'released',
                'released_at' => now(),
            ]);

            if ($reservation->reservable_type === NapPort::class) {
                $napPort = NapPort::query()->lockForUpdate()->find($reservation->reservable_id);

                if ($napPort && $napPort->status === NapPortStatus::RESERVED) {
                    $napPort->update([
                        'status' => NapPortStatus::FREE,
                    ]);
                }
            }
        });
    }

    public function extendReservation(int $reservationId, int $additionalHours): CapacityReservation
    {
        return DB::transaction(function () use ($reservationId, $additionalHours) {
            $reservation = CapacityReservation::query()->lockForUpdate()->findOrFail($reservationId);

            if ($reservation->released_at !== null || $reservation->status !== 'active') {
                throw new BusinessLogicException('Solo se pueden extender reservas activas');
            }

            $baseTime = $reservation->expires_at->isFuture() ? $reservation->expires_at : now();

            $reservation->update([
                'expires_at' => $baseTime->copy()->addHours($additionalHours),
            ]);

            return $reservation->fresh();
        });
    }

    protected function resolveCoordinates(array $addressData): array
    {
        $radiusMeters = (int) ($addressData['radius_meters'] ?? 500);

        if (!empty($addressData['address_id'])) {
            $address = Address::findOrFail((int) $addressData['address_id']);

            if (!$address->hasCoordinates()) {
                throw new BusinessLogicException('La dirección seleccionada no tiene coordenadas');
            }

            return [
                (float) $address->latitude,
                (float) $address->longitude,
                $address->id,
                $radiusMeters,
            ];
        }

        if (!isset($addressData['latitude'], $addressData['longitude'])) {
            throw new BusinessLogicException('Se requieren coordenadas o una dirección con georreferencia');
        }

        return [
            (float) $addressData['latitude'],
            (float) $addressData['longitude'],
            null,
            $radiusMeters,
        ];
    }
}
