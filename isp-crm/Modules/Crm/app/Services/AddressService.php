<?php

declare(strict_types=1);

namespace Modules\Crm\Services;

use Modules\Crm\Entities\Address;

class AddressService
{
    public function normalize(array $addressData): array
    {
        $normalized = $addressData;

        foreach (['street', 'district', 'city', 'province', 'label'] as $field) {
            if (!empty($normalized[$field])) {
                $normalized[$field] = $this->normalizeText($normalized[$field]);
            }
        }

        foreach (['number', 'floor', 'apartment', 'postal_code'] as $field) {
            if (!empty($normalized[$field])) {
                $normalized[$field] = trim((string) $normalized[$field]);
            }
        }

        foreach (['reference', 'address_reference'] as $field) {
            if (!empty($normalized[$field])) {
                $normalized[$field] = trim((string) $normalized[$field]);
            }
        }

        if (isset($normalized['photo_url']) && $normalized['photo_url'] !== null) {
            $normalized['photo_url'] = trim((string) $normalized['photo_url']);
        }

        if (isset($normalized['latitude'])) {
            $normalized['latitude'] = $normalized['latitude'] !== null ? (float) $normalized['latitude'] : null;
        }

        if (isset($normalized['longitude'])) {
            $normalized['longitude'] = $normalized['longitude'] !== null ? (float) $normalized['longitude'] : null;
        }

        $normalized['georeference_quality'] = $this->resolveGeoreferenceQuality(
            $normalized['latitude'] ?? null,
            $normalized['longitude'] ?? null
        );

        return $normalized;
    }

    public function geocode(Address $address): void
    {
        $address->update([
            'georeference_quality' => $this->resolveGeoreferenceQuality(
                $address->latitude !== null ? (float) $address->latitude : null,
                $address->longitude !== null ? (float) $address->longitude : null
            ),
        ]);
    }

    public function validateGeoreference(float $lat, float $lng): bool
    {
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return false;
        }

        if (abs($lat) < 0.000001 && abs($lng) < 0.000001) {
            return false;
        }

        return true;
    }

    protected function resolveGeoreferenceQuality(?float $lat, ?float $lng): ?string
    {
        if ($lat === null || $lng === null) {
            return null;
        }

        if (!$this->validateGeoreference($lat, $lng)) {
            return 'low';
        }

        $decimalPrecision = min(
            $this->countDecimals((string) $lat),
            $this->countDecimals((string) $lng)
        );

        if ($decimalPrecision >= 6) {
            return 'high';
        }

        if ($decimalPrecision >= 4) {
            return 'medium';
        }

        return 'low';
    }

    protected function normalizeText(string $value): string
    {
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);

        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    protected function countDecimals(string $number): int
    {
        $parts = explode('.', rtrim($number, '0'));

        return isset($parts[1]) ? strlen($parts[1]) : 0;
    }
}
