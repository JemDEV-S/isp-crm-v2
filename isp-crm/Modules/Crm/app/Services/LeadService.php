<?php

declare(strict_types=1);

namespace Modules\Crm\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Crm\DTOs\ConvertLeadDTO;
use Modules\Crm\DTOs\CreateLeadDTO;
use Modules\Crm\Entities\Customer;
use Modules\Crm\Entities\Lead;
use Modules\Crm\Enums\LeadStatus;
use Modules\Crm\Events\LeadDuplicateDetected;
use Modules\Crm\Events\LeadConverted;
use Modules\Crm\Events\LeadCreated;
use Modules\Crm\Events\LeadStatusChanged;

class LeadService
{
    public function __construct(
        protected CustomerService $customerService,
        protected DuplicateDetectionService $duplicateDetectionService,
    ) {}

    public function create(CreateLeadDTO $dto): Lead
    {
        $lead = DB::transaction(function () use ($dto) {
            $lead = Lead::create($this->normalizeDuplicateSensitiveData($dto->toArray()));
            $this->syncDuplicateStatus($lead);

            event(new LeadCreated($lead));

            return $lead;
        });

        return $lead->fresh(['duplicateOf']);
    }

    public function update(Lead $lead, array $data): Lead
    {
        $oldStatus = $lead->status;

        $lead->update($this->normalizeDuplicateSensitiveData($data));
        $this->syncDuplicateStatus($lead);

        if (isset($data['status']) && $oldStatus !== $lead->status) {
            event(new LeadStatusChanged($lead, $oldStatus, $lead->status));
        }

        return $lead->fresh(['duplicateOf']);
    }

    public function changeStatus(Lead $lead, LeadStatus $newStatus): Lead
    {
        if ($lead->isFinal()) {
            throw new \DomainException('No se puede cambiar el estado de un lead finalizado');
        }

        $oldStatus = $lead->status;

        $lead->update(['status' => $newStatus]);

        event(new LeadStatusChanged($lead, $oldStatus, $newStatus));

        return $lead->fresh();
    }

    public function assign(Lead $lead, int $userId): Lead
    {
        $lead->update(['assigned_to' => $userId]);

        return $lead->fresh();
    }

    public function convert(ConvertLeadDTO $dto): Customer
    {
        $lead = Lead::findOrFail($dto->leadId);

        if ($lead->isConverted()) {
            throw new \DomainException('Este lead ya fue convertido');
        }

        return DB::transaction(function () use ($lead, $dto) {
            $customer = $this->customerService->createFromLead($lead, $dto);

            $lead->update([
                'status' => LeadStatus::WON,
                'converted_at' => now(),
            ]);

            event(new LeadConverted($lead, $customer));

            return $customer;
        });
    }

    public function markAsLost(Lead $lead, ?string $reason = null): Lead
    {
        if ($lead->isConverted()) {
            throw new \DomainException('No se puede marcar como perdido un lead convertido');
        }

        $oldStatus = $lead->status;

        $lead->update([
            'status' => LeadStatus::LOST,
            'notes' => $reason ? $lead->notes . "\n[Razón de pérdida]: " . $reason : $lead->notes,
        ]);

        event(new LeadStatusChanged($lead, $oldStatus, LeadStatus::LOST));

        return $lead->fresh();
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Lead::query()
            ->with(['zone', 'assignedUser', 'createdByUser']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (!empty($filters['zone_id'])) {
            $query->where('zone_id', $filters['zone_id']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('document_number', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getStats(): array
    {
        return [
            'total' => Lead::count(),
            'duplicates' => Lead::where('is_duplicate', true)->count(),
            'new' => Lead::status(LeadStatus::NEW)->count(),
            'contacted' => Lead::status(LeadStatus::CONTACTED)->count(),
            'qualified' => Lead::status(LeadStatus::QUALIFIED)->count(),
            'won' => Lead::status(LeadStatus::WON)->count(),
            'lost' => Lead::status(LeadStatus::LOST)->count(),
            'conversion_rate' => $this->calculateConversionRate(),
        ];
    }

    public function checkDuplicates(Lead $lead): array
    {
        $matches = $this->duplicateDetectionService->detectDuplicateLeads($lead->toArray(), $lead->id);

        return $matches->map(fn (array $match) => [
            'lead_id' => $match['lead']->id,
            'name' => $match['lead']->name,
            'matched_by' => $match['matched_by'],
            'status' => $match['lead']->status->value,
            'is_converted' => $match['lead']->isConverted(),
        ])->all();
    }

    protected function calculateConversionRate(): float
    {
        $total = Lead::whereIn('status', [LeadStatus::WON, LeadStatus::LOST])->count();

        if ($total === 0) {
            return 0;
        }

        $won = Lead::status(LeadStatus::WON)->count();

        return round(($won / $total) * 100, 2);
    }

    protected function syncDuplicateStatus(Lead $lead): void
    {
        $matches = $this->duplicateDetectionService->detectDuplicateLeads($lead->toArray(), $lead->id);
        $firstMatch = $matches->first();

        if ($firstMatch === null) {
            if ($lead->is_duplicate) {
                $this->duplicateDetectionService->resolveDuplicate($lead, 'auto_cleared');
            }

            return;
        }

        $matchedLead = $firstMatch['lead'];

        $this->duplicateDetectionService->markAsDuplicate($lead, $matchedLead->id);

        event(new LeadDuplicateDetected(
            lead: $lead->fresh(),
            matchedLead: $matchedLead,
            matchedBy: $firstMatch['matched_by'],
        ));
    }

    protected function normalizeDuplicateSensitiveData(array $data): array
    {
        if (array_key_exists('phone', $data) && $data['phone'] !== null) {
            $data['phone'] = preg_replace('/\D+/', '', (string) $data['phone']);
        }

        if (array_key_exists('email', $data) && $data['email'] !== null) {
            $data['email'] = trim(mb_strtolower((string) $data['email']));
        }

        if (array_key_exists('document_number', $data) && $data['document_number'] !== null) {
            $data['document_number'] = trim(mb_strtolower((string) $data['document_number']));
        }

        if (array_key_exists('document_type', $data) && $data['document_type'] !== null) {
            $data['document_type'] = trim(mb_strtolower((string) $data['document_type']));
        }

        return $data;
    }
}
