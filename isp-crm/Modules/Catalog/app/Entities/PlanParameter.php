<?php

declare(strict_types=1);

namespace Modules\Catalog\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanParameter extends Model
{
    protected $fillable = [
        'plan_id',
        'key',
        'value',
        'display_name',
    ];

    /**
     * Standardized parameter keys.
     */
    public const KEYS = [
        'download_speed_mbps',
        'upload_speed_mbps',
        'burst_download_mbps',
        'burst_upload_mbps',
        'burst_threshold',
        'burst_time',
        'priority_queue',
        'address_list',
        'rate_limit_string',
        'vlan_id',
        'connection_limit',
        'fup_gb',
    ];

    /**
     * Get the plan this parameter belongs to.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the display label for this parameter.
     */
    public function getDisplayLabelAttribute(): string
    {
        return $this->display_name ?? $this->getDefaultDisplayName($this->key);
    }

    /**
     * Get the default display name for a key.
     */
    private function getDefaultDisplayName(string $key): string
    {
        $labels = [
            'download_speed_mbps' => 'Velocidad de Descarga (Mbps)',
            'upload_speed_mbps' => 'Velocidad de Subida (Mbps)',
            'burst_download_mbps' => 'Burst de Descarga (Mbps)',
            'burst_upload_mbps' => 'Burst de Subida (Mbps)',
            'burst_threshold' => 'Umbral de Burst',
            'burst_time' => 'Tiempo de Burst',
            'priority_queue' => 'Cola de Prioridad',
            'address_list' => 'Lista de Direcciones',
            'rate_limit_string' => 'Rate Limit String',
            'vlan_id' => 'VLAN ID',
            'connection_limit' => 'Límite de Conexiones',
            'fup_gb' => 'FUP (GB)',
        ];

        return $labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }
}
