<?php

declare(strict_types=1);

namespace Modules\Crm\Services;

use Illuminate\Support\Collection;
use Modules\Crm\Entities\Lead;

class DuplicateDetectionService
{
    public function detectDuplicateLeads(array $data, ?int $ignoreLeadId = null): Collection
    {
        $documentType = $this->normalizeText($data['document_type'] ?? null);
        $documentNumber = $this->normalizeText($data['document_number'] ?? null);
        $phone = $this->normalizePhone($data['phone'] ?? null);
        $email = $this->normalizeText($data['email'] ?? null);

        if ($documentType === null && $documentNumber === null && $phone === null && $email === null) {
            return collect();
        }

        return Lead::query()
            ->when($ignoreLeadId !== null, fn ($query) => $query->where('id', '!=', $ignoreLeadId))
            ->where(function ($query) use ($documentType, $documentNumber, $phone, $email) {
                if ($documentType !== null && $documentNumber !== null) {
                    $query->orWhere(function ($subQuery) use ($documentType, $documentNumber) {
                        $subQuery->whereRaw('LOWER(document_type) = ?', [$documentType])
                            ->whereRaw('LOWER(document_number) = ?', [$documentNumber]);
                    });
                }

                if ($phone !== null) {
                    $query->orWhere('phone', $phone);
                }

                if ($email !== null) {
                    $query->orWhereRaw('LOWER(email) = ?', [$email]);
                }
            })
            ->orderBy('id')
            ->get()
            ->map(fn (Lead $lead) => [
                'lead' => $lead,
                'matched_by' => $this->matchedBy($lead, $documentType, $documentNumber, $phone, $email),
            ])
            ->filter(fn (array $match) => !empty($match['matched_by']))
            ->values();
    }

    public function markAsDuplicate(Lead $lead, ?int $duplicateOfId): void
    {
        $lead->forceFill([
            'is_duplicate' => $duplicateOfId !== null,
            'duplicate_of_id' => $duplicateOfId,
            'duplicate_resolution' => null,
        ])->save();
    }

    public function resolveDuplicate(Lead $lead, string $resolution): void
    {
        $lead->forceFill([
            'is_duplicate' => false,
            'duplicate_resolution' => $resolution,
            'duplicate_of_id' => null,
        ])->save();
    }

    protected function matchedBy(
        Lead $lead,
        ?string $documentType,
        ?string $documentNumber,
        ?string $phone,
        ?string $email
    ): array {
        $matchedBy = [];

        if (
            $documentType !== null &&
            $documentNumber !== null &&
            $this->normalizeText($lead->document_type) === $documentType &&
            $this->normalizeText($lead->document_number) === $documentNumber
        ) {
            $matchedBy[] = 'document';
        }

        if ($phone !== null && $this->normalizePhone($lead->phone) === $phone) {
            $matchedBy[] = 'phone';
        }

        if ($email !== null && $this->normalizeText($lead->email) === $email) {
            $matchedBy[] = 'email';
        }

        return $matchedBy;
    }

    protected function normalizeText(?string $value): ?string
    {
        $value = $value !== null ? trim(mb_strtolower($value)) : null;

        return $value !== '' ? $value : null;
    }

    protected function normalizePhone(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', $value);

        return $normalized !== '' ? $normalized : null;
    }
}
