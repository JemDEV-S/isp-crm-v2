<?php

declare(strict_types=1);

namespace Modules\Network\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckFeasibilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['nullable', 'integer', 'min:100', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.required' => 'La latitud es obligatoria',
            'latitude.between' => 'La latitud debe estar entre -90 y 90',
            'longitude.required' => 'La longitud es obligatoria',
            'longitude.between' => 'La longitud debe estar entre -180 y 180',
            'radius_meters.min' => 'El radio mínimo es de 100 metros',
            'radius_meters.max' => 'El radio máximo es de 2000 metros',
        ];
    }
}
