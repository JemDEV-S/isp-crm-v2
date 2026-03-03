<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Catalog\Enums\AppliesTo;
use Modules\Catalog\Enums\DiscountType;

class StorePromotionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasPermission('catalog.promotion.create');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', 'unique:promotions,code'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'discount_type' => ['required', 'string', Rule::in(DiscountType::values())],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'applies_to' => ['required', 'string', Rule::in(AppliesTo::values())],
            'min_months' => ['nullable', 'integer', 'min:0'],
            'discount_months' => ['nullable', 'integer', 'min:1'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
            'plan_ids' => ['nullable', 'array'],
            'plan_ids.*' => ['integer', 'exists:plans,id'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'code' => 'código',
            'name' => 'nombre',
            'description' => 'descripción',
            'discount_type' => 'tipo de descuento',
            'discount_value' => 'valor del descuento',
            'applies_to' => 'aplica a',
            'min_months' => 'meses mínimos',
            'discount_months' => 'meses de descuento',
            'valid_from' => 'válido desde',
            'valid_until' => 'válido hasta',
            'max_uses' => 'usos máximos',
            'is_active' => 'activo',
            'plan_ids' => 'planes',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'valid_until.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
        ];
    }
}
