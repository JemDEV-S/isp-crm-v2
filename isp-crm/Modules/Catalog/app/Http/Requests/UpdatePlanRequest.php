<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Catalog\Enums\Technology;

class UpdatePlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasPermission('catalog.plan.update');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'technology' => ['sometimes', 'required', 'string', Rule::in(Technology::values())],
            'download_speed' => ['sometimes', 'required', 'integer', 'min:1'],
            'upload_speed' => ['sometimes', 'required', 'integer', 'min:1'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'installation_fee' => ['nullable', 'numeric', 'min:0'],
            'ip_pool_id' => ['nullable', 'integer', 'exists:ip_pools,id'],
            'device_id' => ['nullable', 'integer', 'exists:devices,id'],
            'router_profile' => ['nullable', 'string', 'max:100'],
            'olt_profile' => ['nullable', 'string', 'max:100'],
            'burst_enabled' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:8'],
            'is_active' => ['nullable', 'boolean'],
            'is_visible' => ['nullable', 'boolean'],
            'parameters' => ['nullable', 'array'],
            'parameters.*' => ['string'],
            'promotion_ids' => ['nullable', 'array'],
            'promotion_ids.*' => ['integer', 'exists:promotions,id'],
            'addon_ids' => ['nullable', 'array'],
            'addon_ids.*' => ['integer', 'exists:addons,id'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
            'technology' => 'tecnología',
            'download_speed' => 'velocidad de descarga',
            'upload_speed' => 'velocidad de subida',
            'price' => 'precio',
            'installation_fee' => 'tarifa de instalación',
            'ip_pool_id' => 'pool de IP',
            'device_id' => 'dispositivo',
            'router_profile' => 'perfil de router',
            'olt_profile' => 'perfil de OLT',
            'burst_enabled' => 'burst habilitado',
            'priority' => 'prioridad',
            'is_active' => 'activo',
            'is_visible' => 'visible',
            'promotion_ids' => 'promociones',
            'addon_ids' => 'addons',
        ];
    }
}
